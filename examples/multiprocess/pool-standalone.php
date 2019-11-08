#!/usr/bin/env php
<?php
/**
 * This example shows how to do multiprocessing without IPC (inter-process communication). It doesn't listen or
 * accept any external messages, and you should implement your business logic in the "workerStart" callback.
 *
 * This example creates a pool to run the worker process 3 times.
 * To run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./multiprocess/pool-standalone.php"
 */

use Swoole\Atomic;
use Swoole\Process\Pool;

$pool    = new Pool(1, SWOOLE_IPC_NONE);
$counter = new Atomic(0);

$pool->on("workerStart", function (Pool $pool, int $workerId) use ($counter) {
    echo "Process #{$workerId} started. (STANDALONE)\n";
    $counter->add(1);
});
$pool->on("workerStop", function (Pool $pool, int $workerId) use ($counter) {
    echo "Process #{$workerId} stopped. (STANDALONE)\n";
    if ($counter->get() >= 3) {
        $pool->shutdown();
    }
});

$pool->start();
