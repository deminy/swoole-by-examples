#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to create a process pool to communicate through message queue. Please check script
 * "client.php" under the same directory to see how to communicate with the pool.
 */

use Swoole\Process;
use Swoole\Process\Pool;

$pool = new Pool(swoole_cpu_num(), SWOOLE_IPC_MSGQUEUE, 0x7000001);

$pool->on('message', function (Pool $pool, string $message): void {
    /** @var Process $process */
    $process = $pool->getProcess();
    echo "Process #{$process->id} received message \"{$message}\". (MSGQUEUE)", PHP_EOL;
});
$pool->on('workerStart', function (Pool $pool, int $workerId): void {
    echo "Process #{$workerId} started. (MSGQUEUE)", PHP_EOL;
});
$pool->on('workerStop', function (Pool $pool, int $workerId): void {
    echo "Process #{$workerId} stopped. (MSGQUEUE)", PHP_EOL;
});

$pool->start();
