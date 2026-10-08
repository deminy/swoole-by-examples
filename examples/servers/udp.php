#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a UDP server on port 9506.
 *
 * UDP has no connections: each datagram arrives in the "onPacket" callback together with the sender's address, and the
 * reply is sent back with method $server->sendto().
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. Its output can be viewed with:
 *     docker compose logs -f server
 *
 * To test the UDP server, you can execute a netcat command in the client container to talk to the UDP server:
 *     docker compose exec -ti client nc -u server 9506
 *
 * Now you can start typing something and press Enter to send it to the UDP server. Whatever you type, the UDP server
 * echoes it back.
 *
 * Once done, you can press CTRL+C to stop netcat (the server keeps running).
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
