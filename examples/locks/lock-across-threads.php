<?php

declare(strict_types=1);

/**
 * This example shows how to use locks across threads.
 *
 * Once executed, it prints out "12345678". The numbers printed out are to show the order of the code execution.
 *
 * Note that this example works only with ZTS (Zend Thread Safety) enabled. Swoole 6.1 removed Lock::trylock(); a
 * non-blocking attempt is now made by passing LOCK_EX | LOCK_NB to Lock::lock() instead.
 *
 * How to run this script:
 *     docker run --rm -v "$(pwd):/var/www" -ti phpswoole/swoole:6.2-php8.4-zts php ./examples/locks/lock-across-threads.php
 */

use Swoole\Thread;
use Swoole\Thread\Lock;

$args = Thread::getArguments();
if (!isset($args)) { // The main thread.
    $lock    = new Lock();
    $threads = [];

    $threads[] = new Thread(__FILE__, 1, $lock);
    usleep(20_000); // Starting a thread takes a few milliseconds; give the first thread time to acquire the lock.
    $threads[] = new Thread(__FILE__, 2, $lock);
    foreach ($threads as $thread) {
        $thread->join();
    }

    echo '8', PHP_EOL;
} else {
    $i    = $args[0];
    $lock = $args[1];

    if ($i === 1) { // First child thread.
        echo '1';
        assert($lock->lock() === true, 'Lock the lock for the first time successfully.');
        usleep(100_000); // Hold the lock for 100 milliseconds.
        echo '4';
        assert($lock->unlock() === true, 'Unlock the lock successfully.');
        echo '5';
    } else { // Second child thread.
        echo '2';
        assert($lock->lock(LOCK_EX | LOCK_NB) === false, 'Failed to lock a locked lock.');
        echo '3';
        assert($lock->lock() === true, 'Lock the lock for the second time successfully.');
        usleep(100_000); // Hold the lock for 100 milliseconds.
        echo '6';
        assert($lock->unlock() === true, 'Unlock the lock successfully.');
        echo '7';
    }
}
