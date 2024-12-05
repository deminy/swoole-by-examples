#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to create a process pool to communicate through TCP socket. Please check script "client.php"
 * under the same directory to see how to communicate with the pool.
 */

use Swoole\Process\Pool;

$pool = new Pool(swoole_cpu_num(), SWOOLE_IPC_SOCKET);

$pool->on('message', function (Pool $pool, string $message): void {
    $pool->write("Hello, {$message}!");
});
$pool->on('workerStart', function (Pool $pool, int $workerId): void {
    echo "Process #{$workerId} started. (TCP SOCKET)", PHP_EOL;
});
$pool->on('workerStop', function (Pool $pool, int $workerId): void {
    echo "Process #{$workerId} stopped. (TCP SOCKET)", PHP_EOL;
});

$pool->listen('0.0.0.0', 9701);
$pool->start();
