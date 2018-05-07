<?php declare(strict_types=1);

namespace ReactiveApps\Command\BunnyConsumer;

use Bunny\Async\Client;
use React\EventLoop\LoopInterface;
use ReactiveApps\Command\Command;
use WyriHaximus\React\ObservableBunny\ObservableBunny;

final class BunnyConsumer implements Command
{
    const COMMAND = 'bunny-consumer';

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function __invoke()
    {
        $bunny = new Client($this->loop);
        $observableBunny = new ObservableBunny($this->loop, $bunny);
        $observableBunny->consume('queue:name', [0, 10])->subscribe(function (Message $message) {
            // Handle message
        });
    }
}
