#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a TCP server in coroutine style on port 9507.
 *
 * To test the TCP server, you can execute a netcat command in the client container to talk to the TCP server:
 *     docker compose exec -ti client nc server 9507
 *
 * Now you can start typing something and hit the return button to send it to the TCP server. Whatever you type, the TCP
 * server echos it back.
 *
 * Once done, you can press CTRL+C to stop.
 */

use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;

use function Swoole\Coroutine\run;

run(function (): void {
    $server = new Server('0.0.0.0', 9507);
    $server->handle(function (Connection $conn): void {
        while (true) {
            if ($data = $conn->recv()) {
                $conn->send($data);
            } else {
                $conn->close();
                break;
            }
        }
    });
    $server->start();
});
