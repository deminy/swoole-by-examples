#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to wait for a collection of coroutines to finish in PHP applications. It has 3 coroutines
 * executed, and takes about 3 seconds to finish.
 *
 * Class \Swoole\Coroutine\Barrier is defined in this file:
 *     https://github.com/swoole/library/blob/master/src/core/Coroutine/Barrier.php
 *
 * This example uses class \Swoole\Coroutine\Barrier, which is implemented using destruct methods in PHP. Class
 * \Swoole\Coroutine\Barrier works similar to class \Swoole\Coroutine\WaitGroup, which is implemented using channels
 * (class \Swoole\Coroutine\Channel).
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/waitgroup.php
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./csp/barrier.php"
 */

use Swoole\Coroutine\Barrier;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $barrier = Barrier::make();

    go(function () use ($barrier): void { // @phpstan-ignore closure.unusedUse
        sleep(1);
    });

    go(function () use ($barrier): void { // @phpstan-ignore closure.unusedUse
        sleep(2);
    });
    go(function () use ($barrier): void { // @phpstan-ignore closure.unusedUse
        sleep(3);
    });

    Barrier::wait($barrier); // Wait those 3 coroutines to finish.

    // Any code here won't be executed until all 3 coroutines created in this function finish execution.
});
