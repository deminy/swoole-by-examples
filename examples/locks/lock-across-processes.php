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
 */

use Swoole\Lock;
use Swoole\Process;

$lock = new Lock();

$process1 = new Process(function () use ($lock) {
    echo '1';
    assert($lock->lock() === true, 'Lock the lock for the first time successfully.');
    usleep(5000); // Sleep for 5 milliseconds.
    echo '4';
    assert($lock->unlock() === true, 'Unlock the lock successfully.');
    echo '5';
});

$process2 = new Process(function () use ($lock) {
    echo '2';
    assert($lock->trylock() === false, 'Failed to lock a locked lock.');
    echo '3';
    assert($lock->lock() === true, 'Lock the lock for the second time successfully.');
    echo '6';
    assert($lock->unlock() === true, 'Unlock the lock successfully.');
    echo '7';
});

$process1->start(); // Start the first child process.
$process2->start(); // Start the second child process.

Process::wait();
Process::wait();

echo '8', PHP_EOL;
