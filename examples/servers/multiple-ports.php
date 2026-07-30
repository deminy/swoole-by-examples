#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how a single Swoole server can listen on multiple ports at the same time, with each port
 * having its own set of event callbacks. The server is created on its MAIN port 9530, and an ADDITIONAL port 9531 is
 * attached to the same server process via the $server->listen() method. The object returned by $server->listen() is a
 * Swoole\Server\Port instance, on which we register a separate 'receive' callback. This way, one server process accepts
 * connections on both ports, but responds differently depending on which port a client connected to.
 *
 * Both ports here speak the same raw TCP protocol, keeping the focus on the multi-port mechanics alone. To see the
 * same technique used to serve a DIFFERENT protocol on each port (via per-port protocol settings), check example
 * mixed-protocols-2.php.
 *
 * How to run this script:
 * This script is auto-started by supervisor when the server container boots, so there is no need to start it manually.
 * To test it, you can send data to each port from the client container and observe the per-port responses:
 *     docker compose exec -t client bash -c "echo hello | nc server 9530"
 *     docker compose exec -t client bash -c "echo hello | nc server 9531"
 * The first command returns a reply prefixed with "port 9530", while the second one returns a reply prefixed with
 * "port 9531", demonstrating that a single server handles both ports with per-port callbacks.
 */

use Swoole\Server;

// Create the server; 9530 is the MAIN port. Following the event-driven (base) style, like tcp1.php.
$server = new Server('0.0.0.0', 9530, SWOOLE_BASE, SWOOLE_SOCK_TCP);

// Register a 'receive' callback for the MAIN port (9530).
$server->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, 'port 9530: ' . $data . PHP_EOL);
    }
);

// Attach an ADDITIONAL port (9531) to the same server process. The returned Port object lets us set its own callbacks.
// $server->listen() returns false on failure (e.g., when the port is already in use).
$port = $server->listen('0.0.0.0', 9531, SWOOLE_SOCK_TCP);
if (!$port instanceof Server\Port) {
    exit('Failed to listen on the additional port 9531.' . PHP_EOL);
}

// Register a 'receive' callback for the ADDITIONAL port (9531) via the Port object, independent of the main port.
$port->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, 'port 9531: ' . $data . PHP_EOL);
    }
);

$server->start();
