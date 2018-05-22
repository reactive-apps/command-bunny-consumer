<?php declare(strict_types=1);

namespace ReactiveApps\Command\BunnyConsumer;

use Bunny\Async\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use ReactiveApps\Command\Command;
use ReactiveApps\Rx\Shutdown;
use WyriHaximus\PSR3\CallableThrowableLogger\CallableThrowableLogger;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;
use WyriHaximus\React\ObservableBunny\Message;
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
     * @param Client $bunny
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     * @param Shutdown $shutdown
     */
    public function __construct(Client $bunny, LoopInterface $loop, LoggerInterface $logger, Shutdown $shutdown)
    {
        $this->bunny = $bunny;
        $this->loop = $loop;
        $this->logger = new ContextLogger($logger, ['section' => 'bunny consumer'], 'bunny consumer');
        $this->shutdown = $shutdown;
    }

    public function __invoke()
    {
        $this->logger->debug('Connecting');
        /** @var Client $bunny */
        $bunny = yield $this->bunny->connect();
        $this->logger->debug('Connected');

        $observableBunny = new ObservableBunny($this->loop, $bunny);
        $subject = $observableBunny->consume('foo.bar', [0, 10])->subscribe(function (Message $message) {
            var_export([
                $message->getMessage(),
            ]);
            $message->ack();
        }, CallableThrowableLogger::create($this->logger));

        /**
         * Dispose of the subscription
         */
        $this->shutdown->subscribe(null, null, function () use ($subject) {
            $this->logger->debug('Disposing subscription');
            $subject->dispose();
        });

        /**
         * Give observable bunny a second to clean up
         */
        $this->shutdown->subscribe(null, null, function () use ($bunny) {
            $this->logger->debug('Disconnecting');
            $bunny->disconnect()->done(function () {
                $this->logger->debug('Disconnected');
            }, CallableThrowableLogger::create($this->logger));
        });
    }
}
