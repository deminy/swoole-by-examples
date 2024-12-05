#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block a process using a lock.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-a-process-using-swoole-lock.php"
 *
 * There are other ways to block a process. Check the following example to see how to use class \Swoole\Atomic to do it:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-atomic.php
 */

use Swoole\Coroutine;
use Swoole\Lock;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// The lock created is available to all coroutines within the process.
$lock = new Lock();

// When function Swoole\Coroutine\run() is called, it automatically creates a main coroutine to run the code inside.
run(function () use ($lock): void {
    go(function () use ($lock): void { // A second coroutine is created to block the whole process.
        echo date('H:i:s'), '(coroutine ID: 2)', PHP_EOL;

        // WARNING:
        //    1. Don't keep creating new locks in callback functions of server events (e.g., onReceive, onConnect,
        //       onRequest, etc.). It could lead to memory leak.
        //    2. Avoid using the same lock across different coroutines. It could lead to deadlock. e.g.,
        //       https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/swoole-lock.php
        $lock->lock();
        if ($lock->lockwait(2.0) !== true) { // The lock is released after 2 seconds due to timeout.
            $lock->unlock();
        }

        echo date('H:i:s'), '(coroutine ID: 2)', PHP_EOL;
    });

    // Everything blow is blocked due to the lock acquired by the second coroutine.

    go(function (): void { // A third coroutine is created, which has to wait until the second coroutine releases the lock.
        echo date('H:i:s'), '(coroutine ID: 3)', PHP_EOL;
    });

    // This line is printed out in the main coroutine (created by function call run()).
    echo date('H:i:s'), '(coroutine ID: 1)', PHP_EOL;
});
