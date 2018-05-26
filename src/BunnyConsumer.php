<?php declare(strict_types=1);

namespace ReactiveApps\Command\BunnyConsumer;

use Bunny\Async\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use ReactiveApps\Command\Command;
use ReactiveApps\Rx\Shutdown;
use WyriHaximus\PSR3\CallableThrowableLogger\CallableThrowableLogger;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;
use WyriHaximus\React\ObservableBunny\ObservableBunny;

final class BunnyConsumer implements Command
{
    const COMMAND = 'bunny-consumer';

    /**
     * @var Client
     */
    private $bunny;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Shutdown
     */
    private $shutdown;

    /**
     * @var callable[]
     */
    private $queues = [];

    /**
     * @param Client $bunny
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     * @param Shutdown $shutdown
     * @param callable[] $queues
     */
    public function __construct(Client $bunny, LoopInterface $loop, LoggerInterface $logger, Shutdown $shutdown, array $queues)
    {
        $this->bunny = $bunny;
        $this->loop = $loop;
        $this->logger = new ContextLogger($logger, ['section' => 'bunny consumer'], 'bunny consumer');
        $this->shutdown = $shutdown;
        $this->queues = $queues;
    }

    public function __invoke()
    {
        $this->logger->debug('Connecting');
        /** @var Client $bunny */
        $bunny = yield $this->bunny->connect();
        $this->logger->debug('Connected');

        $observableBunny = new ObservableBunny($this->loop, $bunny, 0.01);
        $subjects = [];
        foreach ($this->queues as $queue => $handler) {
            $subjects[$queue] = $observableBunny->consume($queue, [0, 10])->subscribe($handler, CallableThrowableLogger::create($this->logger));
        }

        /**
         * Dispose of the subscription
         */
        $this->shutdown->subscribe(null, null, function () use ($subjects) {
            $this->logger->debug('Disposing subscription');
            foreach ($subjects as $subject) {
                $subject->dispose();
            }
        });

        /**
         * Give observable bunny a second to clean up
         */
        $this->shutdown->subscribe(null, null, function () use ($bunny) {
            $this->logger->debug('Scheduling disconnect');
            $this->loop->addTimer(1, function () use ($bunny) {
                $this->logger->debug('Disconnecting');
                $bunny->disconnect()->done(function () {
                    var_export($this->loop);
                    $this->logger->debug('Disconnected');
                }, CallableThrowableLogger::create($this->logger));
            });
        });
    }
}
