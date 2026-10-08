#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start ONE server that speaks DIFFERENT protocols on DIFFERENT ports.
 *
 * This is different from example mixed-protocols-same-port.php, where a single port serves multiple protocols
 * (HTTP/1, HTTP/2, and WebSocket) at the same time. Here, each port speaks its own dedicated protocol:
 *     * The primary port speaks HTTP (handled by the primary Swoole\Http\Server and its 'request' callback).
 *     * The additional port speaks a raw TCP protocol (handled by an additional listener and its 'receive' callback).
 *
 * A Swoole server can listen on more than one port. The primary port is bound when the server object is
 * created, while extra ports are added via $server->listen(). Each listener returned by $server->listen()
 * is a Swoole\Server\Port object that can carry its OWN protocol settings (via $port->set([...])) and its
 * OWN event callbacks (via $port->on(...)). Because of this, different listeners of the same server can run
 * completely different protocols independently of each other. Example multiple-ports.php demonstrates the multi-port
 * basics in isolation (the same protocol on every port), without the per-port protocol configuration shown here.
 *
 * To show that, the script starts the server, then sends an HTTP request to both ports: the primary port parses it and
 * answers with an HTTP response, while the additional port treats it as raw bytes and echoes them back as they are.
 * Then the script shuts the server down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/mixed-protocols-per-port.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Client;
use Swoole\Coroutine\Http\Client as HttpClient;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Server\Port;

// The primary listener. Because it is a Swoole\Http\Server, the primary port speaks the HTTP protocol. Port 0 makes the
// server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

// The 'request' callback belongs to the primary HTTP listener.
$server->on(
    'request',
    function (Request $request, Response $response): void {
        $response->end('Hello from the HTTP listener.');
    }
);

// Add a SECOND listener on another random port. The returned Swoole\Server\Port object represents this extra port, can
// be configured independently of the primary HTTP listener above, and exposes the port picked as $tcpPort->port.
// $server->listen() returns false on failure (e.g., when the port is already in use).
$tcpPort = $server->listen('127.0.0.1', 0, SWOOLE_SOCK_TCP);
if (!$tcpPort instanceof Port) {
    exit('Failed to listen on the additional port.' . PHP_EOL);
}

// Configure this listener to speak a raw TCP protocol. Setting 'open_http_protocol' (and its HTTP/2 and
// WebSocket siblings) to false makes sure the port is NOT treated as an HTTP port that it inherits from the
// primary Swoole\Http\Server, so it stays a plain, raw TCP port.
$tcpPort->set(
    [
        'open_http_protocol'      => false,
        'open_http2_protocol'     => false,
        'open_websocket_protocol' => false,
    ]
);

// The 'receive' callback belongs ONLY to the raw TCP listener. Here we simply echo the data back.
$tcpPort->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, $data);
    }
);

// Once the server has started, send an HTTP request to each port (the "workerStart" callback runs in a coroutine).
$server->on(
    'workerStart',
    function (Server $server, int $workerId) use ($tcpPort): void {
        // The primary port parses the request as HTTP, and answers with an HTTP response.
        $client = new HttpClient('127.0.0.1', $server->port);
        $client->get('/');
        echo "Reply from the primary (HTTP) port: HTTP {$client->statusCode}, body: {$client->body}", PHP_EOL;
        $client->close();

        // The additional port treats the very same request as raw bytes, and echoes them back as they are. The reply
        // is printed with json_encode() so that the line breaks ("\r\n") in it stay visible.
        $request = "GET / HTTP/1.1\r\nHost: localhost\r\n\r\n";
        $client  = new Client(SWOOLE_SOCK_TCP);
        $client->connect('127.0.0.1', $tcpPort->port);
        $client->send($request);
        $reply = $client->recv();
        echo 'Reply from the additional (raw TCP) port: ', json_encode($reply, JSON_UNESCAPED_SLASHES), PHP_EOL;
        $client->close();

        $server->shutdown();
    }
);

$server->start();
