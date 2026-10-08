#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how a deadlock happens when a server is shut down improperly.
 *
 * When the server shuts down, each worker process stops its event loop and waits (up to 3 seconds by default) for its
 * coroutines to finish. The coroutine below loops forever, so it never finishes, and Swoole reports it as a deadlock.
 *
 * How to run this script:
 *   docker compose exec -t client bash -c "./csp/deadlocks/server-shutdown.php"
 * When the script is executed, it takes about 5 seconds to finish, and prints out the following error messages
 * (along with a backtrace of the stuck coroutine):
 *   WARNING Worker_reactor_try_to_exit() (ERRNO 9101): worker exit timeout, forced termination
 *   [FATAL ERROR]: all coroutines (count: 1) are asleep - deadlock!
 * To fix the deadlock, uncomment line 46 and line 37, then rerun the script.
 */

use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

// The port number is omitted on purpose (a random unused port is picked): nothing ever connects to this server.
$server = new Server('0.0.0.0');

$server->on('workerStart', function (Server $server, int $workerId): void {
    if ($workerId === 0) { // The first event worker process is used in this example.
        // Create a coroutine that never ends (it sleeps 10 milliseconds per round, forever).
        $cid = Coroutine::create(function (): void {
            while (true) { // @phpstan-ignore while.alwaysTrue
                if (Coroutine::isCanceled()) {
                    // The deadlock can be resolved by uncommenting line 46 and line 37.
                    // break; #2: Quit the infinite loop after the coroutine is canceled.
                }
                Coroutine::sleep(0.01);
            }
        });

        // Shut down the server after 2 seconds.
        Timer::after(2_000, function () use ($server, $cid): void { // @phpstan-ignore closure.unusedUse
            // The deadlock can be resolved by uncommenting line 46 and line 37.
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
