#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block a process using a lock. There are other ways to block a process, e.g.,
 * using method \Swoole\Atomic::wait() and method \Swoole\Atomic::wakeup() together in a multiprocessing environment.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-a-process.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./io/block-a-process.php"
 *
 * This script takes about 2 seconds to finish.
 */

use Swoole\Coroutine;
use Swoole\Lock;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// The lock created is available to all coroutines within the process.
$lock = new Lock();

// When function Swoole\Coroutine\run() is called, it automatically creates a main coroutine to run the code inside.
run(function () use ($lock) {
    go(function () use ($lock) { // A second coroutine is created to block the whole process.
        echo date('H:i:s'), '(coroutine ID: 2)', PHP_EOL;

        $lock->lock();
        if ($lock->lockwait(2.0) !== true) {
            $lock->unlock();
        }

        echo date('H:i:s'), '(coroutine ID: 2)', PHP_EOL;
    });

    // Everything blow is blocked due to the lock acquired by the second coroutine.

    go(function () use ($lock) { // A third coroutine is created, which has to wait until the second coroutine releases the lock.
        echo date('H:i:s'), '(coroutine ID: 3)', PHP_EOL;
    });

    // This line is printed out in the main coroutine (created by function call run()).
    echo date('H:i:s'), '(coroutine ID: 1)', PHP_EOL;
});
