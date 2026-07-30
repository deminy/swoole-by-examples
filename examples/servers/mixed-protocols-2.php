#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start ONE server that speaks DIFFERENT protocols on DIFFERENT ports.
 *
 * This is different from example mixed-protocols-1.php, where a single port serves multiple protocols
 * (HTTP/1, HTTP/2, and WebSocket) at the same time. Here, each port speaks its own dedicated protocol:
 *     * Port 9550 speaks HTTP (handled by the primary Swoole\Http\Server and its 'request' callback).
 *     * Port 9551 speaks a raw TCP protocol (handled by an additional listener and its 'receive' callback).
 *
 * A Swoole server can listen on more than one port. The primary port is bound when the server object is
 * created, while extra ports are added via $server->listen(). Each listener returned by $server->listen()
 * is a Swoole\Server\Port object that can carry its OWN protocol settings (via $port->set([...])) and its
 * OWN event callbacks (via $port->on(...)). Because of this, different listeners of the same server can run
 * completely different protocols independently of each other. Example multiple-ports.php demonstrates the multi-port
 * basics in isolation (the same protocol on every port), without the per-port protocol configuration shown here.
 *
 * How to run this script:
 *     This script is auto-started by supervisor inside the server container, so there is no need to start it
 *     manually. To test it, run following commands:
 *
 *     # To test the HTTP protocol on port 9550:
 *     docker compose exec -t client bash -c "curl -i http://server:9550"
 *
 *     # To test the raw TCP protocol on port 9551 (the server echoes whatever it receives):
 *     docker compose exec -t client bash -c "echo hello | nc server 9551"
 */

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Server\Port;

// The primary listener. Because it is a Swoole\Http\Server, port 9550 speaks the HTTP protocol.
$server = new Server('0.0.0.0', 9550);

// The 'request' callback belongs to the primary HTTP listener on port 9550.
$server->on(
    'request',
    function (Request $request, Response $response): void {
        $response->end('Hello from the HTTP listener on port 9550.' . PHP_EOL);
    }
);

// Add a SECOND listener on port 9551. The returned Swoole\Server\Port object represents this extra port and
// can be configured independently of the primary HTTP listener above.
// $server->listen() returns false on failure (e.g., when the port is already in use).
$tcpPort = $server->listen('0.0.0.0', 9551, SWOOLE_SOCK_TCP);
if (!$tcpPort instanceof Port) {
    exit('Failed to listen on the additional port 9551.' . PHP_EOL);
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

// The 'receive' callback belongs ONLY to the TCP listener on port 9551. Here we simply echo the data back.
$tcpPort->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, $data);
    }
);

$server->start();
