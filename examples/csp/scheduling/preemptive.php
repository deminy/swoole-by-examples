#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, while the first coroutine keeps busy running all the time, the second coroutine still has a chance
 * getting executed after a while, which throws an exception out and terminates the execution.
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
