#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to run an HTTP server in coroutine style, using class \Swoole\Coroutine\Http\Server.
 *
 * Unlike \Swoole\Http\Server (used in most server examples in this repository), a coroutine-style server:
 *   * runs inside a coroutine, in the current process: there are no master, manager, or worker processes.
 *   * is started and stopped like any other code, so it can be embedded in a larger program (e.g., next to other
 *     coroutines).
 *   * handles every request in a coroutine of its own, so slow requests don't block each other.
 * Since everything runs in one process, a coroutine-style server uses one CPU core only. To use more cores, run it in
 * multiple processes, e.g., using a process pool (see the examples under folder "pool/process-pool/").
 *
 * In this example, the server handles each request in 1 second. The script starts the server, makes three requests
 * to it concurrently, then shuts the server down. The three requests take about 1 second in total, not 3.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/coroutine-http-server.php"
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\Http\Server;
use Swoole\Coroutine\WaitGroup;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    // Port 0 lets the operating system pick an unused port. The constructor already binds and listens on the port
    // (which is why $server->port is known here), so clients can connect even before method start() runs.
    $server = new Server('127.0.0.1', 0);

    // Requests are routed by path prefix; '/' matches every request.
    $server->handle('/', function (Request $request, Response $response): void {
        Coroutine::sleep(1); // To simulate a slow request, e.g., a slow database query.
        $response->end(sprintf('Hello from coroutine #%d!', Coroutine::getCid())); // @phpstan-ignore argument.type
    });

    // Method start() keeps running until the server is shut down, so start the server in a coroutine of its own.
    go(function () use ($server): void {
        $server->start();
    });

    $start = microtime(true);
    $wg    = new WaitGroup(3);
    for ($i = 1; $i <= 3; $i++) {
        go(function () use ($server, $wg, $i): void {
            $client = new Client('127.0.0.1', $server->port);
            $client->get('/');
            echo "Response to request #{$i}: {$client->body}", PHP_EOL;
            $client->close();
            $wg->done();
        });
    }
    $wg->wait();
    printf('Three requests, each taking 1 second, finished in about %d second in total.' . PHP_EOL, round(microtime(true) - $start));

    $server->shutdown();
});
