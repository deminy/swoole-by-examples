#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a server to support HTTP/1, HTTP/2, and WebSocket on the same port.
 *
 * A \Swoole\WebSocket\Server is also an HTTP server: requests that aren't WebSocket handshakes are handled by its
 * 'request' callback. With option "open_http2_protocol" turned on, the same port also accepts HTTP/2 connections, and
 * their requests are handled by the same 'request' callback. The server tells the protocols apart by the first bytes
 * that each client sends.
 *
 * To show that, the script starts the server, then sends an HTTP/1 request, an HTTP/2 request, and a WebSocket message
 * to the same port, and prints the three replies. Then the script shuts the server down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/mixed-protocols-same-port.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Http\Client as HttpClient;
use Swoole\Coroutine\Http2\Client as Http2Client;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http2\Request as Http2Request;
use Swoole\Http2\Response as Http2Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0, SWOOLE_BASE);
$server->set(
    [
        Constant::OPTION_WORKER_NUM          => 1,
        Constant::OPTION_OPEN_HTTP2_PROTOCOL => true,
    ]
);

// HTTP/1 and HTTP/2
$server->on(
    'request',
    function (Request $request, Response $response): void {
        $response->end("Hello, {$request->rawContent()}");
    }
);

// WebSocket
$server->on(
    'message',
    function (Server $server, Frame $frame): void {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);

// Once the server has started, talk to it using the three protocols (the "workerStart" callback runs in a coroutine).
$server->on(
    'workerStart',
    function (Server $server, int $workerId): void {
        // HTTP/1
        $client = new HttpClient('127.0.0.1', $server->port);
        $client->post('/', 'HTTP/1');
        echo "HTTP/1 response: {$client->body}", PHP_EOL;
        $client->close();

        // HTTP/2
        $client = new Http2Client('127.0.0.1', $server->port);
        $client->connect();
        $request         = new Http2Request();
        $request->method = 'POST';
        $request->data   = 'HTTP/2';
        $client->send($request);
        $response = $client->recv();
        echo 'HTTP/2 response: ', ($response instanceof Http2Response) ? $response->data : '(none)', PHP_EOL;
        $client->close();

        // WebSocket
        $client = new HttpClient('127.0.0.1', $server->port);
        $client->upgrade('/');
        $client->push('WebSocket');
        $frame = $client->recv();
        echo 'WebSocket message: ', ($frame instanceof Frame) ? $frame->data : '(none)', PHP_EOL;
        $client->close();

        $server->shutdown();
    }
);

$server->start();
