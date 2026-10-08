#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows class \Swoole\Timer: a recurring timer (method tick()) that runs a callback every 100
 * milliseconds, a one-off timer (method after()) that clears it at the 500th millisecond, and method exists() to check
 * whether a timer is still active.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./timer/timer-class.php"
 *
 * The example can be implemented using coroutines only (without the \Swoole\Timer class). Please check script
 * "coroutine-style.php" for details.
 */

use Swoole\Timer;

use function Swoole\Coroutine\run;

run(function (): void {
    $id = Timer::tick(100, function (): void {
        echo 'Function call is triggered every 100 milliseconds by the timer.', PHP_EOL;
    });
    Timer::after(500, function () use ($id): void {
        Timer::clear($id);
        echo 'The timer is cleared at the 500th millisecond.', PHP_EOL;
    });
    Timer::after(1000, function () use ($id): void {
        if (!Timer::exists($id)) {
            echo 'The timer should not exist at the 1,000th millisecond.', PHP_EOL;
        }
    });
});
