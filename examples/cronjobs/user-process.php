#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example runs cron-style scheduled jobs in a DEDICATED USER PROCESS attached to a server via
 * \Swoole\Server::addProcess(). The scheduler lives in its own OS process, so it is isolated from request workers (a
 * heavy or blocking job cannot stall request handling), and there is exactly one scheduler regardless of worker_num -
 * no risk of the same job firing once per worker, the classic pitfall of registering timers in the workerStart event.
 * This is the pattern production frameworks use (e.g., Hyperf's crontab component runs its dispatcher as a custom
 * process). Two caveats worth knowing: the process callback must keep looping (a user process whose callback returns
 * while the server is running gets re-forked immediately, over and over), and $server->reload() does NOT restart user
 * processes - only a full server restart picks up new scheduler code.
 *
 * Graceful shutdown is wired across processes here: when the server shuts down, its manager sends SIGTERM to the
 * user process. The process turns that signal into a closed channel (\Swoole\Coroutine\System::waitSignal() in one
 * coroutine, Channel::pop() as the interruptible sleep in the cron loop - see interruptible-channel.php for that
 * pattern in isolation), so the job wakes instantly, runs one last time, and exits cleanly.
 *
 * This demo is self-terminating: a worker-registered one-shot timer shuts the whole server down after ~3 seconds
 * (timers must not be registered in the master process's "start" event - a worker is the right place). The server
 * listens on a random unused port (port 0) just because a server must listen somewhere; the port is not used.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./cronjobs/user-process.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\System;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Process;
use Swoole\Timer;

// Port 0 makes the server listen on a random unused port; it is passed explicitly only because the server mode
// after it needs to be specified. Nothing ever connects to this server (see the docblock above).
$server = new Server('0.0.0.0', 0, SWOOLE_BASE);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

// The 4th constructor argument enables coroutine support inside the process, so channels and System::waitSignal()
// work in it.
$process = new Process(
    function (): void {
        $shutdown = new Channel();

        // On server shutdown, the manager sends SIGTERM to user processes. Turn that signal into a closed channel,
        // so the cron loop below can finish gracefully.
        Coroutine::create(function () use ($shutdown): void {
            System::waitSignal(SIGTERM, -1);
            $shutdown->close();
        });

        // The job body is a closure so that the regular runs and the final run on shutdown execute the same code.
        $job = function (): void {
            printf('[%s] Job (user process #%d): runs every 1 second.%s', date('H:i:s'), getmypid(), PHP_EOL);
        };

        while (true) {
            $job();
            $shutdown->pop(1.0);
            if ($shutdown->errCode === SWOOLE_CHANNEL_CLOSED) {
                printf('[%s] SIGTERM received; running the job one last time before exiting.%s', date('H:i:s'), PHP_EOL);
                $job();
                break;
            }
        }
        printf('[%s] The cronjob process has exited.%s', date('H:i:s'), PHP_EOL);
    },
    false,
    0,
    true
);
$server->addProcess($process);

$server->on('workerStart', function (Server $server, int $workerId): void {
    // Demo-bounded: stop the whole server (and thereby SIGTERM the cron process) after ~3 seconds.
    if ($workerId === 0) {
        Timer::after(3000, function () use ($server): void {
            $server->shutdown();
        });
    }
});
$server->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});

$server->start();
