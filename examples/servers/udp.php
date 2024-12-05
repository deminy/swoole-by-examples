#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a UDP server on port 9506.
 *
 * To test the UDP server, you can execute a netcat command in the client container to talk to the UDP server:
 *     docker compose exec -ti client nc -u server 9506
 *
 * Now you can start typing something and hit the return button to send it to the UDP server. Whatever you type, the UDP
 * server echos it back.
 *
 * Once done, you can press CTRL+C to stop.
 */

use Swoole\Server;

$server = new Server('0.0.0.0', 9506, SWOOLE_BASE, SWOOLE_SOCK_UDP);
$server->on(
    'packet',
    function (Server $server, string $data, array $clientInfo): void {
        $server->sendto($clientInfo['address'], $clientInfo['port'], $data);
    }
);
$server->start();
