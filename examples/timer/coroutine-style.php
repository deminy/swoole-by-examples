#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The script is to show how to implement timer using coroutines only. Please check script "timer.php" to see the
 * original implementation where class \Swoole\Timer is used.
 *
 * How to run this script:
 *     docker compose exec -t server bash -c "./timer/coroutine-style.php"
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
