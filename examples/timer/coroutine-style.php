#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to implement the same timer as in script "timer-class.php" with coroutines only: a loop that
 * calls \Swoole\Coroutine::sleep(). Please check script "timer-class.php" to see the original implementation where
 * class \Swoole\Timer is used.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./timer/coroutine-style.php"
 */

use Swoole\Coroutine;

use function Swoole\Coroutine\run;

run(function (): void {
    $i = 0;
    while (true) {
        Coroutine::sleep(0.1);
        echo 'Print out this message every 100 milliseconds.', PHP_EOL;
        if (++$i === 5) {
            echo 'Stop printing out messages at the 500th millisecond.', PHP_EOL;
            break;
        }
    }
    echo 'No more messages should be printed out after the 500th millisecond.', PHP_EOL;
});
