#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when a server is shut down or reloaded improperly.
 *
 * How to run this script:
 *   docker run --rm -v $(pwd):/var/www -ti phpswoole/swoole php ./examples/csp/deadlocks/server-shutdown.php
 * When the script is executed, it will print out the following error message:
 *   [FATAL ERROR]: all coroutines (count: 1) are asleep - deadlock!
 * To fix the deadlock, uncomment line 40 and line 31, then rerun the script.
 */

use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

$server = new Server('0.0.0.0');

$server->on('workerStart', function (Server $server, int $workerId): void {
    if ($workerId === 0) { // The first event worker process is used in this example.
        // Create a coroutine that sleeps forever.
        $cid = Coroutine::create(function (): void {
            while (true) { // @phpstan-ignore while.alwaysTrue
                if (Coroutine::isCanceled()) {
                    // The deadlock can be resolved by uncommenting line 40 and line 31.
                    // break; #2: Quit the infinite loop after the coroutine is canceled.
                }
                Coroutine::sleep(0.01);
            }
        });

        // Shutdown the server after 2 seconds.
        Timer::after(2_000, function () use ($server, $cid): void { // @phpstan-ignore closure.unusedUse
            // The deadlock can be resolved by uncommenting line 40 and line 31.
            // Coroutine::cancel($cid); #1: Cancel the coroutine before shutting down the server.

            echo 'The server is shutting down.', PHP_EOL;
            $server->shutdown();
        });
    }
});

// A dummy callback for the "request" event is required for the HTTP server. It has nothing to do with this example.
$server->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});

$server->start();
