<?php

use Bunny\Async\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use ReactiveApps\Command\BunnyConsumer\BunnyConsumer;
use ReactiveApps\Rx\Shutdown;

return [
    BunnyConsumer::class => \DI\factory(function (Client $bunny, LoopInterface $loop, LoggerInterface $logger, Shutdown $shutdown, array $queues) {
        return new BunnyConsumer($bunny, $loop, $logger, $shutdown, $queues);
    })
    ->parameter('queues', \DI\get('config.bunny.queues')),
];
