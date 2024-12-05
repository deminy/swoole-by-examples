#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a server to support HTTP/1, HTTP/2, and WebSocket on same port.
 *
 * You can use following commands to test different protocols:
 * 1. To test HTTP/1:
 *   docker compose exec -t client bash -c "curl -i -d World http://server:9511"
 * 2. To test HTTP/2:
 *   docker compose exec -t client bash -c "curl -i -d World --http2-prior-knowledge http://server:9511"
 * 3. To test WebSocket:
 *   docker compose exec -ti client bash -c "websocat ws://server:9511"
 */
$server = new Swoole\WebSocket\Server('0.0.0.0', 9511, SWOOLE_BASE);
$server->set(
    [
        'open_http2_protocol' => true,
    ]
);

// HTTP/1 and HTTP/2
$server->on(
    'request',
    function (Swoole\Http\Request $request, Swoole\Http\Response $response): void {
        $response->end("Hello, {$request->rawContent()}" . PHP_EOL);
    }
);

// WebSocket
$server->on(
    'message',
    function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame): void {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);

$server->start();
