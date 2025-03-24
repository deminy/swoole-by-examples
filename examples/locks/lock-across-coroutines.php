#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use locks across coroutines.
 *
 * Once executed, it prints out "12345678". The numbers printed out are to show the order of the code execution.
 *
 * Note that class \Swoole\Coroutine\Lock is available only on Swoole 6.0.1 or later.
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
        assert($lock->lock() === true, 'Lock the lock for the first time successfully.');
        Coroutine::sleep(0.005); // Sleep for 5 milliseconds.
        echo '4';
        assert($lock->unlock() === true, 'Unlock the lock successfully.');
        echo '5';
    });

    go(function () use ($lock) { // Start the second coroutine inside the main coroutine.
        echo '2';
        assert($lock->trylock() === false, 'Failed to lock a locked lock.');
        echo '3';
        assert($lock->lock() === true, 'Lock the lock for the second time successfully.');
        echo '6';
        assert($lock->unlock() === true, 'Unlock the lock successfully.');
        echo '7';
    });
});

echo '8', PHP_EOL;
