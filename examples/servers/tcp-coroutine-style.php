#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a TCP server in coroutine style on port 9507.
 *
 * In coroutine style, the server runs inside a coroutine in the current process (no master/manager/worker processes),
 * and each connection is handled by one function running in a coroutine of its own: it calls recv() and send() in a
 * loop, as if it were blocking code. Compare with example tcp-event-driven.php.
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. Its output can be viewed with:
 *     docker compose logs -f server
 *
 * To test the TCP server, you can execute a netcat command in the client container to talk to the TCP server:
 *     docker compose exec -ti client nc server 9507
 *
 * Now you can start typing something and press Enter to send it to the TCP server. Whatever you type, the TCP server
 * echoes it back.
 *
 * Once done, you can press CTRL+C to stop netcat (the server keeps running).
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
