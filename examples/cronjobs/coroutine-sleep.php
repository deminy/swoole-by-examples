#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example implements a cron-style scheduled job as a plain coroutine loop: alternate \Swoole\Coroutine::sleep()
 * with the job body inside \Swoole\Coroutine\run(). Compared to timer-tick.php this reads top-to-bottom, keeps state
 * in local variables, and makes overlapping runs impossible by construction - the next sleep only starts after the
 * job returns, which also gives natural backpressure when a job overruns.
 *
 * The trade-offs of this simplicity:
 *   - The schedule drifts: the effective period is interval + job duration. If long-run cadence matters, sleep
 *     against a computed deadline (e.g. max(0.001, $nextRunAt - microtime(true))) instead of a fixed interval.
 *   - Coroutine::sleep() is NOT interruptible. Nothing can stop the loop mid-sleep; a shutdown request is only
 *     noticed after the current sleep expires. See interruptible-channel.php for the fix - the same loop with
 *     Channel::pop($timeout) as the sleep, which a shutdown can wake instantly.
 *
 * The loop is demo-bounded to three runs so the script self-terminates; a real job would loop forever.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./cronjobs/coroutine-sleep.php"
 */

use Swoole\Coroutine;

use function Swoole\Coroutine\run;

run(function (): void {
    for ($i = 1; $i <= 3; $i++) {
        Coroutine::sleep(1);
        printf('[%s] Job: run %d of 3.%s', date('H:i:s'), $i, PHP_EOL);
    }
    printf('[%s] All runs finished; the script is exiting.%s', date('H:i:s'), PHP_EOL);
});
