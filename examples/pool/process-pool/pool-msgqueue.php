#!/usr/bin/env php
<?php

/**
 * This example shows how to create a process pool to communicate through message queue. Please check script
 * "client.php" under the same directory to see how to communicate with the pool.
 */

use Swoole\Process;
use Swoole\Process\Pool;

$pool = new Pool(swoole_cpu_num(), SWOOLE_IPC_MSGQUEUE, 0x7000001);

$pool->on("message", function (Pool $pool, string $message) {
    /** @var Process $process */
    $process = $pool->getProcess();
    echo "Process #{$process->id} received message \"{$message}\". (MSGQUEUE)\n";
});
$pool->on("workerStart", function (Pool $pool, int $workerId) {
    echo "Process #{$workerId} started. (MSGQUEUE)\n";
});
$pool->on("workerStop", function (Pool $pool, int $workerId) {
    echo "Process #{$workerId} stopped. (MSGQUEUE)\n";
});

$pool->start();
