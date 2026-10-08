#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a WebSocket server on port 9504.
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. Its output can be viewed with:
 *     docker compose logs -f server
 *
 * To test the WebSocket server, you can run following command to get a hello message back from the WebSocket server:
 *     docker compose exec -ti client bash -c "echo Swoole | websocat ws://server:9504"
 */
$server = new Swoole\WebSocket\Server('0.0.0.0', 9504, SWOOLE_BASE);
$server->on(
    'message',
    function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame): void {
        $server->push($frame->fd, "Hello, {$frame->data}!");
    }
);
$server->start();
