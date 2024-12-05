#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to wait for a collection of coroutines to finish in PHP applications. It has 3 coroutines
 * executed, and takes about 3 seconds to finish.
 *
 * Class \Swoole\Coroutine\WaitGroup is defined in this file:
 *     https://github.com/swoole/library/blob/master/src/core/Coroutine/WaitGroup.php
 *
 * This example uses class \Swoole\Coroutine\WaitGroup, which is implemented using channels (class
 * \Swoole\Coroutine\Channel). Class \Swoole\Coroutine\WaitGroup works similar to class \Swoole\Coroutine\Barrier, which
 * doesn't use channels at all.
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/barrier.php
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./csp/waitgroup.php"
 */
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $wg = new WaitGroup();

    go(function () use ($wg): void {
        $wg->add();
        Coroutine::sleep(1);
        $wg->done();
    });

    $wg->add(2); // You don't have to increase the counter one by one.
    go(function () use ($wg): void {
        Coroutine::sleep(2);
        $wg->done();
    });
    go(function () use ($wg): void {
        Coroutine::sleep(3);
        $wg->done();
    });

    $wg->wait(); // Wait those 3 coroutines to finish.

    // Any code here won't be executed until all 3 coroutines created in this function finish execution.
});
