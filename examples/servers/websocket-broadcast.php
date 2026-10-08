#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how a WebSocket server broadcasts a message to all its connected clients, e.g., in a chat room.
 *
 * Property $server->connections iterates over the IDs (file descriptors) of all the connections of the server, across
 * all worker processes, and method $server->push() sends a message to any of them, no matter which worker process
 * the connection belongs to. To broadcast, iterate over the connections, skip those that aren't WebSocket connections
 * (method isEstablished() returns false for them, e.g., while the handshake is still in progress), and push the
 * message to each of the others.
 *
 * This works across worker processes only in SWOOLE_PROCESS mode, where the master process owns all the connections
 * and the worker processes only handle their events. In SWOOLE_BASE mode (the default since Swoole 5.0), each worker
 * process owns the connections it accepted, so $server->connections lists only the current worker's connections, and
 * a broadcast would miss the clients connected to the other workers.
 *
 * In this example, the server runs in SWOOLE_PROCESS mode with two worker processes. Once it starts, the script
 * connects three WebSocket clients (alice, bob, and carol) to it, and has alice send a message. The server broadcasts
 * the message to all three clients, including alice herself, then the script shuts the server down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/websocket-broadcast.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\WaitGroup;
use Swoole\Http\Request;
use Swoole\Timer;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

use function Swoole\Coroutine\go;

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0, SWOOLE_PROCESS);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 2,
    ]
);

$server->on('open', function (Server $server, Request $request): void {
    $server->push($request->fd, 'Welcome!');
});

$server->on('message', function (Server $server, Frame $frame): void {
    $recipients = 0;
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, "Broadcast: {$frame->data}");
            $recipients++;
        }
    }
    echo "Server: message from connection #{$frame->fd} broadcast to {$recipients} connections." . PHP_EOL;
});

$server->on('workerStart', function (Server $server, int $workerId): void {
    if ($workerId !== 0) {
        return;
    }

    // A safety net: shut the server down after 5 seconds in case something goes wrong. Under normal execution, the
    // server is shut down way earlier, and this timer is cleared before it fires.
    $timerId = Timer::after(5000, function () use ($server): void {
        $server->shutdown();
    });

    // Worker #0 also plays the clients, in coroutines (the "workerStart" callback runs in a coroutine). A real chat
    // room would have its clients in other programs, e.g., in web browsers.
    go(function () use ($server, $timerId): void {
        $clients = [];
        foreach (['alice', 'bob', 'carol'] as $name) {
            $client = new Client('127.0.0.1', $server->port);
            $client->upgrade('/');
            $client->recv(5); // Wait for the welcome message: the connection is now fully open on the server side too.
            $clients[$name] = $client;
        }

        $clients['alice']->push('Hello, everyone! (from alice)');

        $wg = new WaitGroup(count($clients));
        foreach ($clients as $name => $client) {
            go(function () use ($name, $client, $wg): void {
                $frame = $client->recv(5);
                // A single string per echo statement: the clients print concurrently, and an echo statement with
                // multiple arguments (one write per argument) could interleave with the other clients' output.
                echo "Client {$name} received: " . (($frame instanceof Frame) ? $frame->data : '(nothing)') . PHP_EOL;
                $client->close();
                $wg->done();
            });
        }
        $wg->wait();

        Coroutine::sleep(0.1); // Give the server a moment to notice the closed connections before shutting down.
        Timer::clear($timerId); // A pending timer would keep this worker process from exiting.
        $server->shutdown();
    });
});

$server->start();
