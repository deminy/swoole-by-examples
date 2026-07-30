#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example runs cron-style scheduled jobs in a standalone \Swoole\Process\Pool - no web server involved. The
 * pool manager supervises the workers: a crashed worker is re-forked automatically, SIGTERM to the manager shuts the
 * pool down, and SIGUSR1 restarts workers one by one (rolling reload of job code - something user-process.php's
 * addProcess() approach does not get, since $server->reload() never restarts user processes). This is the shape to
 * pick when cron should be its own deployable, supervised and scaled independently of the web server (e.g., its own
 * Supervisord program or container).
 *
 * Two things this example demonstrates beyond pool basics:
 *   - Sharding the schedule by worker ID. With more than one pool worker, an unguarded job would run once per
 *     worker - the same duplicate-run pitfall as registering timers in every server worker. Here worker #0 runs
 *     job A and worker #1 runs job B, so each job still runs exactly once.
 *   - Graceful shutdown. Pool workers get NO default signal handlers: an unhandled SIGTERM would kill a worker
 *     mid-job. Each worker therefore turns SIGTERM into a closed channel (\Swoole\Coroutine\System::waitSignal() in
 *     one coroutine, Channel::pop() as the interruptible sleep - see interruptible-channel.php for that pattern in
 *     isolation), runs its job one last time, and exits cleanly. The 4th constructor argument enables coroutine
 *     support inside the workers, which this relies on.
 *
 * This demo is self-terminating: worker #0 calls $pool->shutdown() after ~3 seconds, which makes the manager send
 * SIGTERM to every worker - triggering exactly the graceful path described above. In real use the pool would keep
 * running until stopped from outside.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./cronjobs/process-pool.php"
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\System;
use Swoole\Process\Pool;

$pool = new Pool(2, SWOOLE_IPC_NONE, 0, true);

$pool->on('workerStart', function (Pool $pool, int $workerId): void {
    printf('[%s] Worker #%d started (process ID: %d).%s', date('H:i:s'), $workerId, getmypid(), PHP_EOL);

    $shutdown = new Channel();
    Coroutine::create(function () use ($shutdown): void {
        System::waitSignal(SIGTERM, -1);
        $shutdown->close();
    });

    if ($workerId === 0) {
        // Demo-bounded: ask the manager to shut the pool down after ~3 seconds. The manager then sends SIGTERM to
        // every worker, including this one.
        Coroutine::create(function () use ($pool): void {
            Coroutine::sleep(3);
            $pool->shutdown();
        });
    }

    // The schedule is sharded by worker ID so that each job runs in exactly one worker. The job body is a closure
    // so that the regular runs and the final run on shutdown execute the same code.
    [$jobName, $interval] = $workerId === 0 ? ['Job A', 1] : ['Job B', 2];
    $job                  = function () use ($jobName, $workerId, $interval): void {
        printf('[%s] %s (worker #%d): runs every %d second(s).%s', date('H:i:s'), $jobName, $workerId, $interval, PHP_EOL);
    };

    while (true) {
        $job();
        $shutdown->pop($interval);
        if ($shutdown->errCode === SWOOLE_CHANNEL_CLOSED) {
            printf('[%s] Worker #%d: SIGTERM received; running %s one last time before exiting.%s', date('H:i:s'), $workerId, $jobName, PHP_EOL);
            $job();
            break;
        }
    }
    printf('[%s] Worker #%d has exited.%s', date('H:i:s'), $workerId, PHP_EOL);
});

$pool->start();
