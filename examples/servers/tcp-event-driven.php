#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a TCP server in event-driven style on port 9505.
 *
 * To test the TCP server, you can execute a netcat command in the client container to talk to the TCP server:
 *     docker compose exec -ti client nc server 9505
 *
 * Now you can start typing something and hit the return button to send it to the TCP server. Whatever you type, the TCP
 * server echos it back.
 *
 * Once done, you can press CTRL+C to stop.
 */

use Swoole\Server;

$server = new Server('0.0.0.0', 9505);
$server->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, $data);
    }
);
$server->start();
