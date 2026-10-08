#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1 server with DDoS protection enabled.
 *
 * DDoS protection in Swoole-based application server can be implemented by:
 *     1. setting option \Swoole\Constant::OPTION_ENABLE_DELAY_RECEIVE to true. The server then doesn't read anything
 *        from a new connection until the connection is approved.
 *     2. using method \Swoole\Server::confirm() in the callback function of event "onConnect" to approve a connection,
 *        right away or later (e.g., after checking the client's IP address against a blocklist or a rate limiter).
 *
 * For the HTTP server created in this example, 1/3 of the connections (those with a connection ID divisible by 3) are
 * approved 2 seconds later; the others are approved right away.
 *
 * To show that, the script starts the server, sends 6 HTTP requests to it at the same time over 6 connections, and
 * prints how long each one took. Two of them take about 2 seconds; the others get their responses back right away.
 * Then the script shuts the server down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/ddos-protection.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\WaitGroup;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

use function Swoole\Coroutine\go;

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0);

$server->set(
    [
        // For testing purpose, there is only one worker process to handle HTTP requests.
        Constant::OPTION_WORKER_NUM           => 1,
        Constant::OPTION_ENABLE_DELAY_RECEIVE => true,
    ]
);

// In this example, 1/3 of the traffic is processed at a later time, but not in real time. In reality,
// there are different ways to enable DDoS protection, like rate limiting, blocking IP address, etc.
$server->on('connect', function (Server $server, int $fd, int $reactorId): void {
    if (($fd % 3) === 0) {
        // 1/3 of all connections have to wait for two seconds before being processed.
        Timer::after(2000, function () use ($server, $fd): void {
            $server->confirm($fd);
        });
    } else {
        // 2/3 of all connections are processed immediately by the server.
        $server->confirm($fd);
    }
});
$server->on('request', function (Request $request, Response $response): void {
    $response->end("OK (connection #{$request->fd})");
});

// Once the server has started, send 6 requests to it at the same time (the "workerStart" callback runs in a
// coroutine), and print how long each one took.
$server->on('workerStart', function (Server $server, int $workerId): void {
    $durations = [];
    $wg        = new WaitGroup(6);
    for ($i = 1; $i <= 6; $i++) {
        go(function () use ($server, $wg, &$durations): void {
            $start  = microtime(true);
            $client = new Client('127.0.0.1', $server->port);
            $client->get('/');
            $durations[] = $duration = microtime(true) - $start;
            // A single string per echo statement: the requests finish concurrently, and an echo statement with
            // multiple arguments (one write per argument) could interleave with the output of another request.
            echo sprintf('Response "%s" received after %.1f seconds.', $client->body, $duration) . PHP_EOL;
            $client->close();
            $wg->done();
        });
    }
    $wg->wait();

    $delayed = count(array_filter($durations, fn (float $duration): bool => $duration >= 1.5));
    echo 'Requests answered right away: ', 6 - $delayed, '; requests delayed by about 2 seconds: ', $delayed, PHP_EOL;

    $server->shutdown();
});

$server->start();
