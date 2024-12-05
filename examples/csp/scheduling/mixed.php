#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we set PHP option "swoole.enable_preemptive_scheduler" to 1 at line 19, allowing different
 * coroutines to share the CPU. However, when the first coroutine is started, it immediately disables the scheduler
 * defined in Swoole (at line 25), starts printing out 100,000 integers, then enables the scheduler. The second
 * coroutine could be executed only after the scheduler is enabled at line 29.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/scheduling/mixed.php"
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
