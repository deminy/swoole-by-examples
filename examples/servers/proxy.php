#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a TCP reverse-proxy server in coroutine style. It sits in front of an upstream server, and
 * forwards every incoming connection's raw bytes to that upstream server, then relays the upstream response back to
 * the client. In short, it is a simple TCP-level reverse proxy: it doesn't parse the traffic, so it would work the same
 * for any protocol where the client speaks first.
 *
 * To show that, the script starts two servers in the same process: a small upstream HTTP server, and the proxy in front
 * of it. Then it sends an HTTP request to the proxy, and prints the response relayed back from the upstream server.
 * Finally, it shuts both servers down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/proxy.php"
 */

use Swoole\Coroutine\Client;
use Swoole\Coroutine\Http\Client as HttpClient;
use Swoole\Coroutine\Http\Server as HttpServer;
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    // The upstream server: a small HTTP server. Port 0 makes a server listen on a random unused port; the port picked
    // is exposed as $upstream->port.
    $upstream = new HttpServer('127.0.0.1', 0);
    $upstream->handle('/', function (Request $request, Response $response): void {
        $response->status(234, 'Test'); // A custom status code, to make it easy to tell where the response comes from.
        $response->end('Hello from the upstream server!');
    });
    go(function () use ($upstream): void {
        $upstream->start();
    });

    // The proxy server.
    $proxy = new Server('127.0.0.1', 0);
    $proxy->handle(function (Connection $conn) use ($upstream): void {
        // Read the raw bytes the client sent to the proxy (e.g. a full HTTP request).
        $data = $conn->recv();
        if (empty($data)) { // The client sent nothing or the connection was closed; nothing to proxy.
            $conn->close();
            return;
        }

        // Open a fresh upstream connection for this client connection.
        $client = new Client(SWOOLE_SOCK_TCP);
        if (!$client->connect('127.0.0.1', $upstream->port)) {
            $conn->close();
            return;
        }

        // Forward the client's bytes upstream, read the upstream reply, then relay it back to the client.
        // recv() returns false on error/timeout and an empty string when the upstream closes the connection;
        // in both cases there is simply nothing to relay back.
        $client->send($data);
        $response = $client->recv();
        if (is_string($response) && $response !== '') {
            $conn->send($response);
        }

        // Tear down both the upstream connection and the client connection.
        $client->close();
        $conn->close();
    });
    go(function () use ($proxy): void {
        $proxy->start();
    });

    // Send an HTTP request to the proxy (not to the upstream server).
    $client = new HttpClient('127.0.0.1', $proxy->port);
    $client->get('/');
    echo "Response through the proxy: HTTP {$client->statusCode}, body: {$client->body}", PHP_EOL;
    $client->close();

    $proxy->shutdown();
    $upstream->shutdown();
});
