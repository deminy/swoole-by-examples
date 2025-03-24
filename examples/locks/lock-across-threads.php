<?php

declare(strict_types=1);

/**
 * This example shows how to use locks across threads.
 *
 * Once executed, it prints out "12345678". The numbers printed out are to show the order of the code execution.
 *
 * Note that this example works only on Swoole 6.0.0 or later, with ZTS (Zend Thread Safety) enabled.
 *
 * How to run this script:
 *     docker run --rm -v "$(pwd):/var/www" -ti phpswoole/swoole:6.0-zts php ./examples/locks/lock-across-threads.php
 */

use Swoole\Thread;
use Swoole\Thread\Lock;

if (version_compare(SWOOLE_VERSION, '6.0.0', '<')) {
    fwrite(STDERR, 'Error: Swoole 6.0.0 or higher is required. Current version: ' . SWOOLE_VERSION . PHP_EOL);
    exit(1);
}

$args = Thread::getArguments();
if (!isset($args)) { // The main thread.
    $lock    = new Lock();
    $threads = [];

    $threads[] = new Thread(__FILE__, 1, $lock);
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
        usleep(5000); // Sleep for 5 milliseconds.
        echo '4';
        assert($lock->unlock() === true, 'Unlock the lock successfully.');
        echo '5';
    } else { // Second child thread.
        echo '2';
        assert($lock->trylock() === false, 'Failed to lock a locked lock.');
        echo '3';
        assert($lock->lock() === true, 'Lock the lock for the second time successfully.');
        echo '6';
        assert($lock->unlock() === true, 'Unlock the lock successfully.');
        echo '7';
    }
}
