#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block a process using a lock.
 *
 * Class \Swoole\Lock is a process-level lock: when it's already held, lock() blocks the whole process, including all
 * the coroutines in it. In this example, the second coroutine holds the lock and then tries to acquire it again with
 * a 2-second timeout, which blocks the whole process for 2 seconds. The script takes about 2 seconds to finish, and
 * prints out something like the following:
 *     12:00:00 (coroutine ID: 2)
 *     12:00:02 (coroutine ID: 2)
 *     12:00:02 (coroutine ID: 3)
 *     12:00:02 (coroutine ID: 1)
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-a-process-using-swoole-lock.php"
 *
 * There are other ways to block a process. Check the following example to see how to use class \Swoole\Atomic to do it:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-atomic.php
 *
 * Note that Swoole 6.1 removed Lock::lockwait(); the same wait-with-timeout is now done by passing LOCK_EX and a
 * timeout to Lock::lock() instead.
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
        echo date('H:i:s'), ' (coroutine ID: 2)', PHP_EOL;

        // WARNING:
        //    1. Don't keep creating new locks in callback functions of server events (e.g., onReceive, onConnect,
        //       onRequest, etc.). It could lead to memory leak.
        //    2. Avoid using the same lock across different coroutines. It could lead to deadlock. e.g.,
        //       https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/swoole-lock.php
        $lock->lock();
        // The lock is already held, so this second attempt blocks the whole process for 2 seconds, times out and
        // returns false; then the lock is released.
        if ($lock->lock(LOCK_EX, 2.0) !== true) {
            $lock->unlock();
        }

        echo date('H:i:s'), ' (coroutine ID: 2)', PHP_EOL;
    });

    // Everything below is blocked due to the lock acquired by the second coroutine.

    go(function (): void { // A third coroutine. It's created only after the process stops being blocked.
        echo date('H:i:s'), ' (coroutine ID: 3)', PHP_EOL;
    });

    // This line is printed out in the main coroutine (created by function call run()).
    echo date('H:i:s'), ' (coroutine ID: 1)', PHP_EOL;
});
