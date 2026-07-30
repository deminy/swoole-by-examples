#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to detach a worker process from a process pool using method Pool::detach().
 *
 * When a worker calls $pool->detach() from inside itself, the pool manager stops managing that worker: the
 * worker is removed from the pool, and the pool IMMEDIATELY forks a replacement worker to keep the pool size
 * constant. The detached process, however, keeps running independently and is no longer under the pool
 * manager's control, so the manager will neither wait for it nor kill it. This is useful when a worker needs
 * to run a long/slow task without blocking the pool or being terminated by the pool manager: detach first,
 * then finish the long task at your own pace, and finally exit the process by yourself.
 *
 * Note that Pool::detach() only works when the pool is created with SWOOLE_IPC_NONE. With any other IPC mode
 * the pool manages workers through message channels, and a detached worker can no longer receive messages,
 * so detaching is only meaningful (and only supported) for standalone, IPC-free pools.
 *
 * This example creates a pool of size two. The very first worker to start detaches itself, simulates a long
 * task, and exits on its own; the pool then spawns a replacement so that two workers keep running. A shared
 * \Swoole\Atomic counter bounds the demo so that the pool shuts down once enough workers have started,
 * instead of forking replacements forever.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./pool/process-pool/detach.php"
 */

use Swoole\Atomic;
use Swoole\Process\Pool;

$pool     = new Pool(2, SWOOLE_IPC_NONE);
$counter  = new Atomic(0); // Counts how many workers have started, so the demo can terminate cleanly.
$detached = new Atomic(0); // Ensures that only ONE worker performs the detach-and-long-task demonstration.

$pool->on('workerStart', function (Pool $pool, int $workerId) use ($counter, $detached): void {
    $pid = $pool->getProcess()->pid; // @phpstan-ignore property.nonObject
    echo "Process #{$workerId} (process ID in the OS: {$pid}) started.", PHP_EOL;

    $started = $counter->add(1);

    // Let exactly one worker demonstrate the detach semantics. cmpset() atomically flips the flag from 0 to 1
    // for the winning worker only, so no other worker enters this branch.
    if ($detached->cmpset(0, 1)) {
        echo "Process #{$workerId} is detaching from the pool; the pool will spawn a replacement worker.", PHP_EOL;

        // After this call, the pool stops managing this worker and immediately forks a replacement to keep the
        // pool size at two. This worker is now on its own: the pool manager will not wait for it or kill it.
        $pool->detach();

        // Simulate a long/slow task that we didn't want to run under the pool manager's control.
        echo "Detached process #{$workerId} is running a long task independently...", PHP_EOL;
        sleep(2);
        echo "Detached process #{$workerId} finished its long task and is exiting on its own.", PHP_EOL;

        // A detached worker is responsible for terminating itself; it won't be recycled by the pool manager.
        exit(0);
    }

    // Ordinary workers do their normal work here. Once enough workers have started (the initial two plus the
    // one replacement forked after the detach), shut the pool down so the demo doesn't run forever.
    if ($started >= 3) {
        $pool->shutdown();
    }
});
$pool->on('workerStop', function (Pool $pool, int $workerId): void {
    $pid = $pool->getProcess()->pid; // @phpstan-ignore property.nonObject
    echo "Process #{$workerId} (process ID in the OS: {$pid}) stopped.", PHP_EOL;
});

$pool->start();
