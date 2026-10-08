#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we start a web server with a cron job set up to run every 19 seconds.
 *
 * A cron job inside a long-running server is often written as a loop that sleeps between runs (e.g.,
 * Coroutine::sleep(19)). The problem is that a sleeping loop can't be woken up early: if the server shuts down 15
 * seconds into a 19-second sleep, the job never gets a chance to run one last time (e.g., to flush buffered data)
 * before the server exits.
 *
 * In this example, we use a Channel to schedule the cron job to run every 19 seconds: the cron job waits on the
 * Channel (method pop() with a 19-second timeout) instead of sleeping. When the server shuts down, the "onWorkerExit"
 * callback closes the Channel, which interrupts the wait right away; the cron job notices that the Channel is closed,
 * executes one last time, and exits. ("onWorkerExit" is triggered when a worker process is asked to stop while it
 * still has pending work, like the waiting cron job here.)
 *
 * To show that, the script shuts the server down by itself 2 seconds after starting it, well before the 19 seconds are
 * up. The output shows the cron job executing once when the server starts ("case 1"), and once more when the server
 * shuts down ("case 2"), about 2 seconds later instead of 19.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/interruptible-sleep.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

$exited = new Channel();

// Port 0 makes the server listen on a random unused port.
$server = new Server('127.0.0.1', 0);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

$server->on('workerStart', function (Server $server, int $workerId) use ($exited): void {
    Coroutine::create(function () use ($exited): void {
        $start = microtime(true);
        while (true) {
            echo '[INTERRUPTIBLE-SLEEP] Simulating cron job execution. (case 1)', PHP_EOL;
            $exited->pop(19); // Wait for 19 seconds, unless the Channel is closed earlier.
            if ($exited->errCode === SWOOLE_CHANNEL_CLOSED) {
                printf(
                    '[INTERRUPTIBLE-SLEEP] Simulating cron job execution. (case 2, after %d seconds)' . PHP_EOL,
                    round(microtime(true) - $start)
                );
                break;
            }
        }
        echo '[INTERRUPTIBLE-SLEEP] The cron job has exited.', PHP_EOL;
    });

    // Shut the server down 2 seconds later. In a real application, this happens when the server is stopped or
    // restarted (e.g., by a process manager like Supervisord or systemd).
    Timer::after(2000, function () use ($server): void {
        echo '[INTERRUPTIBLE-SLEEP] Shutting down the server.', PHP_EOL;
        $server->shutdown();
    });
});

$server->on('workerExit', function (Server $server, int $workerId) use ($exited): void {
    echo "[INTERRUPTIBLE-SLEEP] Worker #{$workerId} is exiting.", PHP_EOL;
    Coroutine::create(function () use ($exited): void {
        $exited->close();
    });
});

// The web server's own job: answering HTTP requests. Nothing sends requests to it in this example.
$server->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});

$server->start();
