#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how a deadlock happens when using the same \Swoole\Lock object across different coroutines.
 *
 * \Swoole\Lock is a process-level lock: when it's already held, lock() blocks the whole process, not just the current
 * coroutine. Coroutine #2 blocks the process while waiting for the lock, so coroutine #1 can never wake up from its
 * sleep() to release it. Nothing is printed, and the script hangs forever (press Ctrl+C to stop it).
 *
 * Unlike the other deadlock examples in this repository, this example does not show deadlock information (a fatal
 * error message from Swoole), making it hard to find out what's wrong.
 *
 * When using \Swoole\Lock objects in coroutines, make sure there is no coroutine context switching (to switch execution
 * between different coroutines) between method calls to lock() and unlock(). Alternatively, you can use class
 * \Swoole\Coroutine\Lock instead if you need to use locks across coroutines. Check the following example to see how to
 * use class \Swoole\Coroutine\Lock:
 *      https://github.com/deminy/swoole-by-examples/blob/master/examples/locks/lock-across-coroutines.php
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/deadlocks/swoole-lock.php" # It will run forever.
 */

use Swoole\Coroutine;
use Swoole\Lock;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $lock = new Lock();

    go(function () use ($lock): void {
        $lock->lock();       // 1. The lock is acquired.
        Coroutine::sleep(1); // 2. The sleep() method call will switch execution to another coroutine.
        $lock->unlock();
    });

    go(function () use ($lock): void {
        // 3. The lock is held by coroutine #1, so this blocks the whole process: coroutine #1 can never unlock it.
        $lock->lock();
        Coroutine::sleep(1);
        $lock->unlock();
    });
});
