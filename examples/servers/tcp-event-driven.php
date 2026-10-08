#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a TCP server in event-driven style on port 9505.
 *
 * In event-driven style, you register callbacks (here "onReceive") on a \Swoole\Server, and Swoole calls them in its
 * worker processes whenever something happens on a connection. Compare with example tcp-coroutine-style.php, where a
 * single function handles a whole connection in a loop, reading and writing as if it were blocking code.
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. Its output can be viewed with:
 *     docker compose logs -f server
 *
 * To test the TCP server, you can execute a netcat command in the client container to talk to the TCP server:
 *     docker compose exec -ti client nc server 9505
 *
 * Now you can start typing something and press Enter to send it to the TCP server. Whatever you type, the TCP server
 * echoes it back.
 *
 * Once done, you can press CTRL+C to stop netcat (the server keeps running).
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
