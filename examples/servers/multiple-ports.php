#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how a single Swoole server can listen on multiple ports at the same time, with each port
 * having its own set of event callbacks. The server is created on its MAIN port, and an ADDITIONAL port is attached to
 * the same server via the $server->listen() method. The object returned by $server->listen() is a Swoole\Server\Port
 * instance, on which we register a separate 'receive' callback. This way, one server accepts connections on both
 * ports, but responds differently depending on which port a client connected to.
 *
 * Both ports here speak the same raw TCP protocol, keeping the focus on the multi-port mechanics alone. To see the
 * same technique used to serve a DIFFERENT protocol on each port (via per-port protocol settings), check example
 * mixed-protocols-per-port.php.
 *
 * To show that, the script starts the server, sends "hello" to each of the two ports, prints the replies, then shuts
 * the server down. Each reply comes from the callback registered for the port it was sent to.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/multiple-ports.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Client;
use Swoole\Server;

// Create the server on its MAIN port. Following the event-driven (base) style, like tcp-event-driven.php. Port 0 makes
// the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0, SWOOLE_BASE, SWOOLE_SOCK_TCP);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

// Register a 'receive' callback for the MAIN port.
$server->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, "[callback of the main port] {$data}");
    }
);

// Attach an ADDITIONAL port to the same server. The returned Port object lets us set its own callbacks, and exposes the
// port picked as $port->port. $server->listen() returns false on failure (e.g., when the port is already in use).
$port = $server->listen('127.0.0.1', 0, SWOOLE_SOCK_TCP);
if (!$port instanceof Server\Port) {
    exit('Failed to listen on the additional port.' . PHP_EOL);
}

// Register a 'receive' callback for the ADDITIONAL port via the Port object, independent of the main port.
$port->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, "[callback of the additional port] {$data}");
    }
);

// Once the server has started, send "hello" to each port (the "workerStart" callback runs in a coroutine).
$server->on(
    'workerStart',
    function (Server $server, int $workerId) use ($port): void {
        foreach (['main' => $server->port, 'additional' => $port->port] as $name => $portNumber) {
            $client = new Client(SWOOLE_SOCK_TCP);
            $client->connect('127.0.0.1', $portNumber);
            $client->send('hello');
            $reply = $client->recv();
            echo "The {$name} port replied: ", is_string($reply) ? $reply : '(nothing)', PHP_EOL;
            $client->close();
        }

        $server->shutdown();
    }
);

$server->start();
