#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use locks across processes.
 *
 * Once executed, it prints out "12345678". The numbers printed out are to show the order of the code execution.
 *
 * How to run this script:
 *     docker compose exec -t client ./locks/lock-across-processes.php
 *
 * Note that class \Swoole\Lock is not safe to use across coroutines. For details, please check this example:
 *   https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/swoole-lock.php
 *
 * Note also that Swoole 6.1 removed Lock::trylock(); a non-blocking attempt is now made by passing
 * LOCK_EX | LOCK_NB to Lock::lock() instead.
 */

use Swoole\Lock;
use Swoole\Process;

$lock = new Lock();

$process1 = new Process(function () use ($lock) {
    echo '1';
    assert($lock->lock() === true, 'Lock the lock for the first time successfully.');
    usleep(100_000); // Hold the lock for 100 milliseconds.
    echo '4';
    assert($lock->unlock() === true, 'Unlock the lock successfully.');
    echo '5';
});

$process2 = new Process(function () use ($lock) {
    echo '2';
    assert($lock->lock(LOCK_EX | LOCK_NB) === false, 'Failed to lock a locked lock.');
    echo '3';
    assert($lock->lock() === true, 'Lock the lock for the second time successfully.');
    usleep(100_000); // Hold the lock for 100 milliseconds.
    echo '6';
    assert($lock->unlock() === true, 'Unlock the lock successfully.');
    echo '7';
});

$process1->start(); // Start the first child process.
usleep(20_000);     // Give the first child process time to acquire the lock.
$process2->start(); // Start the second child process.

Process::wait();
Process::wait();

echo '8', PHP_EOL;
