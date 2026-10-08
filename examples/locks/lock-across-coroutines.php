#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use locks across coroutines.
 *
 * Class \Swoole\Coroutine\Lock pauses only the waiting coroutine, while class \Swoole\Lock blocks the whole process
 * (see example "csp/deadlocks/swoole-lock.php").
 *
 * Once executed, it prints out "12345678". The numbers printed out are to show the order of the code execution.
 *
 * Note that Swoole 6.1 removed Lock::trylock(); a non-blocking attempt is now made by passing LOCK_EX | LOCK_NB to
 * Lock::lock() instead.
 *
 * How to run this script:
 *     docker compose exec -t client ./locks/lock-across-coroutines.php
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Lock;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

$lock = new Lock();

run(function () use ($lock) {
    go(function () use ($lock) { // Start the first coroutine inside the main coroutine.
        echo '1';
        $locked = $lock->lock();
        assert($locked === true, 'Lock the lock for the first time successfully.');
        Coroutine::sleep(0.005); // Sleep for 5 milliseconds.
        echo '4';
        $unlocked = $lock->unlock();
        assert($unlocked === true, 'Unlock the lock successfully.');
        echo '5';
    });

    go(function () use ($lock) { // Start the second coroutine inside the main coroutine.
        echo '2';
        $locked = $lock->lock(LOCK_EX | LOCK_NB);
        assert($locked === false, 'Failed to lock a locked lock.');
        echo '3';
        $locked = $lock->lock();
        assert($locked === true, 'Lock the lock for the second time successfully.');
        echo '6';
        $unlocked = $lock->unlock();
        assert($unlocked === true, 'Unlock the lock successfully.');
        echo '7';
    });
});

echo '8', PHP_EOL;
