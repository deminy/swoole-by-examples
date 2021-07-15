#!/usr/bin/env php
<?php

/**
 * This example shows how to create a process pool to communicate through TCP socket. Please check script "client.php"
 * under the same directory to see how to communicate with the pool.
 */

use Swoole\Process\Pool;

$pool = new Pool(swoole_cpu_num(), SWOOLE_IPC_SOCKET);

$pool->on('message', function (Pool $pool, string $message) {
    $pool->write("Hello, {$message}!");
});
$pool->on('workerStart', function (Pool $pool, int $workerId) {
    echo "Process #{$workerId} started. (TCP SOCKET)\n";
});
$pool->on('workerStop', function (Pool $pool, int $workerId) {
    echo "Process #{$workerId} stopped. (TCP SOCKET)\n";
});

$pool->listen('0.0.0.0', 9701);
$pool->start();
