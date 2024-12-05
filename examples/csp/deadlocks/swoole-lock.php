#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when using the same \Swoole\Lock object across different coroutines.
 *
 * Different from other deadlock examples in this repository, this example does not show deadlock information (a fatal
 * error message from Swoole), making it hard to find out what's wrong.
 *
 * When using \Swoole\Lock objects in coroutines, make sure there is no coroutine context switching (to switch execution
 * between different coroutines) between method calls to lock() and unlock().
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
        $lock->lock();       // 3. Trying to acquire the lock again? This will cause deadlock.
        Coroutine::sleep(1);
        $lock->unlock();
    });
});
