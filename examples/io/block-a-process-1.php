#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block processes using method \Swoole\Atomic::wait() and \Swoole\Atomic::wakeup()
 * in a multiprocessing environment.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-a-process-1.php"
 *
 * There are other ways to block a process. Check the following example to see how to use class \Swoole\Lock to do it:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-process-2.php
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Process\Pool;

$atomic0 = new Swoole\Atomic(); // To block process #0.
$atomic1 = new Swoole\Atomic(); // To block process #1.
$pool    = new Pool(3, SWOOLE_IPC_NONE); // A pool of 3 processes will be created.
$pool->set(
    [
        Constant::OPTION_ENABLE_COROUTINE => true,
    ]
);

$pool->on('workerStart', function (Pool $pool, int $workerId) use ($atomic0, $atomic1) {
    switch ($workerId) {
        case 0: // Process #0 (first process).
            // Method wait() will block the process until another process wakes it up or the timeout expires. In our
            // case, the timeout expires and the method call to wait() returns false.
            $atomic0->wait(0.7);
            echo "Process #{$workerId} is blocked for 0.7 second before restarting.", PHP_EOL;
            break;
        case 1: // Process #1.
            echo "Process #{$workerId} is blocked forever and wait another process to wake it up.", PHP_EOL;
            // Method wait() will block the process until another process wakes it up. The timeout never expires because
            // we set it to -1.
            $atomic1->wait(-1);
            echo "Process #{$workerId} is waken up and will shutdown the pool.", PHP_EOL;
            $pool->shutdown();
            break;
        case 2: // Process #2.
            Coroutine::sleep(3.000);
            echo "Process #{$workerId} will wake up process #1.", PHP_EOL;
            $atomic1->wakeup();
            break;
        default:
            echo "Error: process #{$workerId} not handled properly.", PHP_EOL;
            break;
    }
});

$pool->start();
