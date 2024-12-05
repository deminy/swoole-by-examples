#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script creates 3 coroutines and takes about 5 seconds to finish.
 * the sleep() methods are to simulate some IO operations in PHP. In blocking mode, it should take about 6 seconds to
 * finish; in non-blocking mode, it takes about 5 seconds to finish.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/nested.php"
 *     # You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/nested.php"
 *
 * To get better understanding on how the code is executed in order, please check script "nested-debug.php" under the
 * same directory.
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
