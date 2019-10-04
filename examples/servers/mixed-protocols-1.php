#!/usr/bin/env php
<?php
/**
 * In this example we start a server to support HTTP/1, HTTP/2, and WebSocket on same port.
 *
 * You can use following commands to test different protocols:
 * 1. To test HTTP/1:
 *     curl -i -d TOM http://127.0.0.1:9511
 * 2. To test WebSocket:
 *     websocat ws://127.0.0.1:9511
 */

$server = new Swoole\WebSocket\Server("0.0.0.0", 9511, SWOOLE_BASE);
$server->set(
    [
        "open_http2_protocol" => true,
    ]
);

// HTTP/1 and HTTP/2
$server->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end("Hello, {$request->rawContent()}\n");
    }
);

// WebSocket
$server->on(
    "message",
    function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);

$server->start();
