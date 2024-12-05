#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block processes using locks.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-processes-using-swoole-lock.php"
 *
 * There are other ways to block a process. Check the following example to see how to use class \Swoole\Atomic to do it:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-atomic.php
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Lock;
use Swoole\Process\Pool;

$pool = new Pool(3, SWOOLE_IPC_NONE); // A pool of 3 processes will be created.
$pool->set(
    [
        Constant::OPTION_ENABLE_COROUTINE => true,
    ]
);

// WARNING:
//    1. Don't keep creating new locks in callback functions of server events (e.g., onReceive, onConnect,
//       onRequest, etc.). It could lead to memory leak.
//    2. Avoid using the same lock across different coroutines. It could lead to deadlock. e.g.,
//       https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/swoole-lock.php
$lock = new Lock(); // The lock created is available to all forked processes within the pool.
$lock->lock();

// In this example, we will use a pool of 3 processes:
//   - Process #0 and #1 are blocked in sequence and waiting another process (process #3) to wake them up.
//   - Process #2 will wake up process #1 and #2 in sequence; afterwords it will shutdown the pool and exit the program.
$pool->on('workerStart', function (Pool $pool, int $workerId) use ($lock): void {
    Coroutine::sleep(max(0.001, $workerId * 0.1)); // Used only to better order the output from different processes.

    switch ($workerId) {
        case 0: // Process #0.
        case 1: // Process #1.
            echo "Process #{$workerId} is blocked and waiting another process (process #2) to wake it up.", PHP_EOL;
            // Since the lock is already acquired by the main process of the pool at line 29, here the two child
            // processes are blocked until the lock is released and then acquired.
            $lock->lock();
            echo "Process #{$workerId} is waken up.", PHP_EOL;
            break;
        case 2: // Process #2.
            echo 'Process #2 is waking up process #0 and #1.', PHP_EOL;
            for ($i = 0; $i < 2; $i++) {
                $lock->unlock(); // To wake up process #0 and #1 in sequence.
                Coroutine::sleep(0.1); // Used only to better order the output.
            }

            $pool->shutdown(); // Done with the example. Now lets shutdown the pool and exit the program.
            break;
        default:
            echo "Error: process #{$workerId} not handled properly.", PHP_EOL;
            break;
    }

    // Blocks current process for a minute. This is to prevent recreating processes in the pool before the program exits.
    Coroutine::sleep(60);
});

$pool->start();
