#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example implements a cron-style scheduled job whose sleep is INTERRUPTIBLE, using Channel::pop() with a
 * timeout in place of \Swoole\Coroutine::sleep(). The pop() call returns in two relevant ways:
 *   - the timeout elapsed ($channel->errCode === SWOOLE_CHANNEL_TIMEOUT): time to run the job again;
 *   - the channel was closed ($channel->errCode === SWOOLE_CHANNEL_CLOSED): a shutdown was requested - the loop
 *     wakes INSTANTLY (no waiting out the interval), gets a chance to run the job one last time, and exits.
 *
 * This solves the problem coroutine-sleep.php demonstrates: a plain sleep cannot be interrupted, so a shutdown
 * request would go unnoticed until the current sleep expires. With a Channel-based sleep the job stops promptly no
 * matter how long its interval is, which makes this the strongest general-purpose pattern for a standalone scheduler
 * (it keeps the coroutine loop's no-overlap property, and a push() to the channel can double as an on-demand manual
 * trigger). The same pattern runs inside a web server in servers/interruptible-sleep.php, where worker shutdown
 * closes the channel, and across processes in user-process.php, where a SIGTERM closes it.
 *
 * In this self-terminating demo, a second coroutine plays the role of the shutdown requester: it closes the channel
 * mid-interval after ~2.5 seconds.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./cronjobs/interruptible-channel.php"
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

use function Swoole\Coroutine\run;

run(function (): void {
    $shutdown = new Channel();

    // Simulates an external stop request arriving mid-interval (a signal handler or a worker-exit event would play
    // this role in a real deployment).
    Coroutine::create(function () use ($shutdown): void {
        Coroutine::sleep(2.5);
        $shutdown->close();
    });

    // The job body is a closure so that the regular runs and the final run on shutdown execute the same code.
    $job = function (): void {
        printf('[%s] Job: runs every 1 second.%s', date('H:i:s'), PHP_EOL);
    };

    while (true) {
        $job();
        $shutdown->pop(1.0); // "Sleeps" for up to 1 second - unless the channel gets closed, which wakes it instantly.
        if ($shutdown->errCode === SWOOLE_CHANNEL_CLOSED) {
            printf('[%s] Stop requested; running the job one last time before exiting.%s', date('H:i:s'), PHP_EOL);
            $job();
            break;
        }
    }
    printf('[%s] The cronjob has exited.%s', date('H:i:s'), PHP_EOL);
});
