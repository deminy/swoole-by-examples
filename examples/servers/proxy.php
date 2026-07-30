#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a TCP reverse-proxy server in coroutine style on port 9520. It sits in front of the HTTP/1
 * server (which listens on 127.0.0.1:9501 inside the same "server" container) and forwards every incoming connection's
 * raw bytes to that upstream server, then relays the upstream response back to the client. In short, it is a simple
 * TCP-level reverse proxy.
 *
 * How to run this script:
 * This script is started automatically by supervisor when the "server" container boots (see the supervisor config file
 * proxy.conf), so there is nothing to start manually. To test it, send an HTTP request through the proxy; the request
 * is forwarded to the HTTP/1 server, whose response (status 234) is relayed back:
 *     docker compose exec -t client bash -c "curl -i http://server:9520"
 */

use Swoole\Coroutine\Client;
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;

use function Swoole\Coroutine\run;

// The upstream server to forward traffic to. Here it is the HTTP/1 server listening inside the same container.
const UPSTREAM_HOST = '127.0.0.1';
const UPSTREAM_PORT = 9501;

run(function (): void {
    $server = new Server('0.0.0.0', 9520);
    $server->handle(function (Connection $conn): void {
        // Read the raw bytes the client sent to the proxy (e.g. a full HTTP request).
        $data = $conn->recv();
        if (empty($data)) { // The client sent nothing or the connection was closed; nothing to proxy.
            $conn->close();
            return;
        }

        // Open a fresh upstream connection to the HTTP/1 server for this client connection.
        $upstream = new Client(SWOOLE_SOCK_TCP);
        if (!$upstream->connect(UPSTREAM_HOST, UPSTREAM_PORT)) {
            $conn->close();
            return;
        }

        // Forward the client's bytes upstream, read the upstream reply, then relay it back to the client.
        // recv() returns false on error/timeout and an empty string when the upstream closes the connection;
        // in both cases there is simply nothing to relay back.
        $upstream->send($data);
        $response = $upstream->recv();
        if (is_string($response) && $response !== '') {
            $conn->send($response);
        }

        // Tear down both the upstream connection and the client connection.
        $upstream->close();
        $conn->close();
    });
    $server->start();
});
