#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we set php.ini directive "swoole.enable_preemptive_scheduler" to 1 at line 20, allowing different
 * coroutines to share the CPU (see example "preemptive.php" under the same directory). However, when the first
 * coroutine is started, it immediately turns off preemptive scheduling (method Swoole\Coroutine::disableScheduler(), at
 * line 26), starts printing out 100,000 integers, then turns it back on. The second coroutine could be executed only
 * after preemptive scheduling is turned back on at line 30; it then throws an exception, which stops the script.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/scheduling/toggle-preemptive-scheduler.php"
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

ini_set('swoole.enable_preemptive_scheduler', '1');

run(
    function (): void {
        go(function (): void {
            $i = 0;
            Swoole\Coroutine::disableScheduler();
            while ($i < 100000) {
                echo $i++, PHP_EOL;
            }
            Swoole\Coroutine::enableScheduler();
        });

        go(function (): never {
            throw new Exception('Quitting.');
        });
    }
);
