#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example implements cron-style scheduled jobs using \Swoole\Timer::tick() - the classic event-loop timer, and
 * the default choice for recurring jobs in a Swoole process. Unlike traditional cron (one-minute minimum granularity,
 * a new process per run), Swoole timers run inside a single long-lived process, support sub-second intervals, and
 * share memory/state between runs.
 *
 * Timer::tick() is fixed-rate: the schedule stays anchored to the original registration, so the cadence does not
 * drift over time no matter how long each run takes. Every tick callback runs in a freshly created coroutine, which
 * cuts both ways: a slow job does not delay later ticks, but ticks of a job slower than its own interval will overlap
 * (guard with a flag or lock if that matters), and an uncaught exception in a callback kills the whole process - wrap
 * job bodies in try/catch, as job A below demonstrates. Timers are also interval-only: for calendar semantics ("every day at 03:00") you would
 * re-arm \Swoole\Timer::after() against a computed \DateTimeImmutable instead.
 *
 * The timers are registered inside \Swoole\Coroutine\run(), which starts Swoole's event loop and returns once every
 * timer has been cleared. To keep this example self-terminating, a one-shot \Swoole\Timer::after() clears all timers
 * via \Swoole\Timer::clearAll() after ~5 seconds; in a real scheduler the timers would simply keep running.
 *
 * The other examples in this folder implement the same idea differently: coroutine-sleep.php (jobs that must never
 * overlap), interruptible-channel.php (jobs that must stop promptly on shutdown), process-pool.php (cron as its own
 * supervised deployable), and tick-to-task.php and user-process.php (schedulers running as part of a web server).
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./cronjobs/timer-tick.php"
 */

use Swoole\Timer;

use function Swoole\Coroutine\run;

run(function (): void {
    // Job A runs every 1 second (1,000 milliseconds). Its body is wrapped in try/catch, as every real timer job
    // should be: an uncaught exception in a timer callback is fatal to the whole process.
    Timer::tick(1000, function (): void {
        try {
            printf('[%s] Job A: runs every 1 second.%s', date('H:i:s'), PHP_EOL);
        } catch (Throwable $t) {
            printf('[%s] Job A failed: %s%s', date('H:i:s'), $t->getMessage(), PHP_EOL);
        }
    });

    // Job B runs every 2 seconds (2,000 milliseconds).
    Timer::tick(2000, function (): void {
        printf('[%s] Job B: runs every 2 seconds.%s', date('H:i:s'), PHP_EOL);
    });

    // After ~5 seconds, clear ALL timers so no more jobs fire and the event loop drains, letting the script exit.
    Timer::after(5000, function (): void {
        Timer::clearAll();
        printf('[%s] All timers cleared; the script is exiting.%s', date('H:i:s'), PHP_EOL);
    });
});
