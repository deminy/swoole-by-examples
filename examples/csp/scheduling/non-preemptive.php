#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, the first coroutine keeps running all the time, while the second coroutine has no chance of getting
 * executed. The script will keep printing out integers.
 *
 * Swoole coroutines are cooperative (non-preemptive) by default: a coroutine keeps the CPU until it pauses by itself
 * (e.g., for I/O, sleep() or a channel). The first coroutine only echoes in a loop and never pauses, so the second
 * coroutine never gets a turn. The script prints numbers forever; press Ctrl+C to stop it.
 *
 * To see how the preemptive scheduler changes this, please check example "preemptive.php" under the same directory.
 *
 * How to run this script:
 *     docker compose exec -ti client bash -c "./csp/scheduling/non-preemptive.php"
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(
    function (): void {
        go(function (): void {
            $i = 0;
            while (true) { // @phpstan-ignore while.alwaysTrue
                echo $i++, PHP_EOL;
            }
        });

        go(function (): never {
            throw new Exception('Quitting.');
        });
    }
);
