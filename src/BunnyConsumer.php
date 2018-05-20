<?php declare(strict_types=1);

namespace ReactiveApps\Command\BunnyConsumer;

use Bunny\Async\Client;
use React\EventLoop\LoopInterface;
use ReactiveApps\Command\Command;
use ReactiveApps\Rx\Shutdown;
use WyriHaximus\React\ObservableBunny\Message;
use WyriHaximus\React\ObservableBunny\ObservableBunny;

final class BunnyConsumer implements Command
{
    const COMMAND = 'bunny-consumer';

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Shutdown
     */
    private $shutdown;

    /**
     * @param LoopInterface $loop
     * @param Shutdown $shutdown
     */
    public function __construct(LoopInterface $loop, Shutdown $shutdown)
    {
        $this->loop = $loop;
        $this->shutdown = $shutdown;
    }

    public function __invoke()
    {
        /** @var Client $bunny */
        $bunny = yield (new Client($this->loop))->connect();
        $observableBunny = new ObservableBunny($this->loop, $bunny);
        $subject = $observableBunny->consume('queue:name', [0, 10])->subscribe(function (Message $message) {
            // Handle message
        });

        /**
         * Dispose of the subscription
         */
        $this->shutdown->subscribe(null, null, function () use ($subject) {
            $subject->dispose();
        });

        /**
         * Give observable bunny a second to clean up
         */
        $this->shutdown->subscribe(null, null, function () use ($bunny) {
            $this->loop->addTimer(1, function () use ($bunny) {
                $bunny->disconnect();
            });
        });
    }
}
