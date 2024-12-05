#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script creates 1,000,000 coroutines in a single process; each coroutine sleeps for 5 seconds.
 * WARNING: This example requires better CPU/memory to run; it may not work on your laptop/desktop.
 *
 * How to run this script:
 *     docker run --rm -v $(pwd)/examples/csp/coroutines/benchmark.php:/benchmark.php -t phpswoole/swoole bash -c \
 *         "/benchmark.php"
 *     # or,
 *     docker compose exec -t client bash -c "./examples/csp/coroutines/benchmark.php"
 *
 * You can run following commands to see how much time it takes to run the script:
 *     docker run --rm -v $(pwd)/examples/csp/coroutines/benchmark.php:/benchmark.php -t phpswoole/swoole bash -c \
 *         "time /benchmark.php"
 *     # or,
 *     docker compose exec -t client bash -c "time ./examples/csp/coroutines/benchmark.php"
 *
 * Following is an output I got when running from a Docker container in an Amazon EC2 m5.2xlarge instance (8 vCPU, 32GB
 * memory). As you can see, it takes less than 10 seconds to create 1,000,000 coroutines, and takes less than 20 seconds
 * to finish executing the whole script:
 *     0100000 active coroutines; total time: 0.909670 seconds; memory usage: 852949384.
 *     0200000 active coroutines; total time: 1.708000 seconds; memory usage: 1705198016.
 *     0300000 active coroutines; total time: 2.525856 seconds; memory usage: 2558495168.
 *     0400000 active coroutines; total time: 3.412931 seconds; memory usage: 3409695168.
 *     0500000 active coroutines; total time: 4.350526 seconds; memory usage: 4260895168.
 *     0600000 active coroutines; total time: 5.246825 seconds; memory usage: 5116289472.
 *     0700000 active coroutines; total time: 6.340518 seconds; memory usage: 5967489472.
 *     0800000 active coroutines; total time: 7.313115 seconds; memory usage: 6818689472.
 *     0900000 active coroutines; total time: 8.320620 seconds; memory usage: 7669889472.
 *     1000000 active coroutines; total time: 9.575437 seconds; memory usage: 8521089472.
 *
 *     real    0m18.511s
 *     user    0m6.230s
 *     sys     0m7.270s
 */

use Swoole\Constant;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

ini_set('memory_limit', -1);

$totalCoroutines = 1_000_000;
$startTime       = microtime(true);
Coroutine::set(
    [
        Constant::OPTION_MAX_COROUTINE => $totalCoroutines,
    ]
);

run(function () use ($totalCoroutines, $startTime): void {
    for ($i = $totalCoroutines; $i--;) {
        go(function (): void {
            sleep(5);
        });

        if (($i % 100_000) === 0) {
            printf(
                '%07d active coroutines; total time: %f seconds; memory usage: %d.' . PHP_EOL,
                count(Coroutine::listCoroutines()), // @phpstan-ignore argument.type
                microtime(true) - $startTime,
                memory_get_usage()
            );
        }
    }
});
