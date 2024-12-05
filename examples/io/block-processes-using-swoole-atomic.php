#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block processes using method \Swoole\Atomic::wait() and \Swoole\Atomic::wakeup()
 * in a multiprocessing environment.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-processes-using-swoole-atomic.php"
 *
 * There are other ways to block processes. Check following examples to see how to use class \Swoole\Lock to do it:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-process-using-swoole-lock.php
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-lock.php
 */

use Swoole\Atomic;
use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Process\Pool;

$atomic = new Atomic(); // To block process #1 and #2.
$pool   = new Pool(4, SWOOLE_IPC_NONE); // A pool of 4 processes will be created.
$pool->set(
    [
        Constant::OPTION_ENABLE_COROUTINE => true,
    ]
);

// In this example, we will use a pool of 4 processes:
//   - Process #0 will be blocked for 10 milliseconds.
//   - Process #1 and #2 are blocked forever and waiting another process (process #3) to wake them up.
//   - Process #3 will wake up process #1 and #2; afterwords it will shutdown the pool and exit the program.
$pool->on('workerStart', function (Pool $pool, int $workerId) use ($atomic): void {
    Coroutine::sleep(max(0.001, $workerId * 0.1)); // Used only to better order the output from different processes.

    switch ($workerId) {
        case 0: // Process #0 (first process).
            // Method wait() will block the process until another process wakes it up or the timeout expires. In our
            // case, the timeout expires and the method call to wait() returns false.
            (new Atomic())->wait(0.01);
            echo 'Process #0 is blocked for 10 milliseconds (0.01 second).', PHP_EOL;
            break;
        case 1: // Process #1.
        case 2: // Process #2.
            echo "Process #{$workerId} is blocked and waiting another process (process #3) to wake it up.", PHP_EOL;
            // Method wait() will block current process until another process (process #3) wakes it up. The timeout
            // never expires because we set it to -1.
            $atomic->wait(-1);

            Coroutine::sleep($workerId * 0.2); // Used only to better order the output.
            echo "Process #{$workerId} is waken up.", PHP_EOL;
            break;
        case 3: // Process #3.
            echo 'Process #3 is waking up process #1 and #2.', PHP_EOL;
            $atomic->wakeup(2); // To wake up process #1 and #2.

            Coroutine::sleep(1);
            $pool->shutdown(); // Done with the example. Now lets shutdown the pool and exit the program.
            break;
        default:
            echo "Error: process #{$workerId} not handled properly.", PHP_EOL;
            break;
    }

    // Blocks current process forever. This is to prevent recreating processes in the pool.
    (new Atomic())->wait(-1);
});

$pool->start();
