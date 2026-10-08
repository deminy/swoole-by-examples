#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, while the first coroutine keeps busy running all the time, the second coroutine still has a chance
 * of getting executed after a while, which throws an exception and terminates the execution.
 *
 * With php.ini directive "swoole.enable_preemptive_scheduler" turned on, Swoole interrupts a coroutine that has been
 * running for too long (about 10 milliseconds) and lets other coroutines run. So the second coroutine gets a turn and
 * throws an exception, which stops the script with an "Uncaught Exception: Quitting." fatal error.
 *
 * Without the preemptive scheduler, the second coroutine never gets a turn; see example "non-preemptive.php" under the
 * same directory.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/scheduling/preemptive.php"
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

ini_set('swoole.enable_preemptive_scheduler', '1');

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
