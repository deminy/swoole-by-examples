#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to do multiprocessing without IPC (inter-process communication). It doesn't listen or
 * accept any external messages, and you should implement your business logic in the "workerStart" callback.
 *
 * This example creates a pool of size one (one process only in the pool), with three worker processes created
 * sequentially.
 *
 * To run this script:
 *     docker compose exec -t client bash -c "./pool/process-pool/pool-standalone.php"
 */

use Swoole\Atomic;
use Swoole\Process\Pool;

$pool    = new Pool(1, SWOOLE_IPC_NONE);
$counter = new Atomic(0);

$pool->on('workerStart', function (Pool $pool, int $workerId) use ($counter): void {
    // For standalone process pool, business logic should be implemented inside this "workerStart" callback.
    echo "Process #{$workerId} (process ID in the OS: {$pool->getProcess()->pid}) started.", PHP_EOL; // @phpstan-ignore property.nonObject
    $counter->add(1);
});
$pool->on('workerStop', function (Pool $pool, int $workerId) use ($counter): void {
    echo "Process #{$workerId} (process ID in the OS: {$pool->getProcess()->pid}) stopped.", PHP_EOL; // @phpstan-ignore property.nonObject
    if ($counter->get() >= 3) {
        $pool->shutdown();
    }
});

$pool->start();
