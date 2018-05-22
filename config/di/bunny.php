<?php

use Bunny\Async\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

return [
    Client::class => \DI\factory(function (
        LoopInterface $loop,
        LoggerInterface $logger,
        string $host,
        string $vhost,
        string $user,
        string $password
    ) {
        return new Client($loop, [
            'host' => $host,
            'port' => 5672,
            'vhost' => $vhost,
            'user' => $user,
            'password' => $password,
            'hearthbeat' => 300,
        ], new ContextLogger($logger, [], 'bunny'));
    })
    ->parameter('host', \DI\get('config.bunny.host'))
    ->parameter('vhost', \DI\get('config.bunny.vhost'))
    ->parameter('user', \DI\get('config.bunny.user'))
    ->parameter('password', \DI\get('config.bunny.password')),
];
