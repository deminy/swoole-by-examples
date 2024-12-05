#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * How to run this script:
 *     docker compose exec -t server bash -c "./timer/timer.php"
 *
 * Check the output and see how Timer works in Swoole.
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
