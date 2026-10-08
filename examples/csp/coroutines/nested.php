#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script runs 3 coroutines (including the main one created by function run()) and takes about 5 seconds to
 * finish.
 *
 * The sleep() calls are to simulate some I/O operations in PHP. In blocking mode (sleep() pauses the whole process),
 * it would take about 6 seconds to finish; in non-blocking mode (sleep() pauses only the current coroutine, thanks to
 * the runtime hooks enabled by function run()), it takes about 5 seconds to finish.
 *
 * A new coroutine starts running right away, and control goes back to its creator only when the new coroutine pauses
 * (e.g., in sleep()) or finishes.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/nested.php"
 *     # You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/nested.php"
 *
 * To get better understanding on how the code is executed in order, please check script "nested-execution-order.php"
 * under the same directory.
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    go(function (): void {
        sleep(3);
        go(function (): void {
            sleep(2);
        });
    });
    sleep(1);
});
