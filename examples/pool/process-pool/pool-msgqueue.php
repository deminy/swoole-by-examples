#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to create a process pool to communicate through message queue.
 *
 * With IPC mode SWOOLE_IPC_MSGQUEUE, a process pool reads messages from a System V message queue (identified by the
 * key passed as the third argument), and each message is handled by one of the worker processes, in the 'message'
 * callback. Any process on the same machine (here: in the same container) can send work to the pool by putting
 * messages into the queue, e.g., with PHP functions msg_get_queue() and msg_send() from PHP extension "sysvmsg".
 * Check PHP manual https://www.php.net/sem for details.
 *
 * To show that, the first worker process plays the client once the pool has started: it sends three messages to the
 * queue, waits until the other worker processes have handled all of them, then shuts the pool down.
 *
 * PHP extension "sysvmsg" is installed only in the server container, so this example must run from there.
 *
 * How to run this script:
 *     docker compose exec -t server bash -c "./pool/process-pool/pool-msgqueue.php"
 */

use Swoole\Atomic;
use Swoole\Process\Pool;

// A message queue key derived from this file, so that it doesn't clash with message queues used by other programs.
$key = ftok(__FILE__, 'q');

// Count the worker processes started and the messages handled. An Atomic object is backed by shared memory, so it must
// be created before the worker processes are started, so that all of them share it.
$started = new Atomic();
$handled = new Atomic();

$pool = new Pool(3, SWOOLE_IPC_MSGQUEUE, $key);

$pool->on('message', function (Pool $pool, string $message) use ($handled): void {
    $id = $pool->getProcess()->id; // @phpstan-ignore property.nonObject
    echo "Process #{$id} received message \"{$message}\".", PHP_EOL;
    $handled->add();
});

$pool->on('workerStart', function (Pool $pool, int $workerId) use ($key, $started, $handled): void {
    $started->add();

    // The other worker processes return right away, so that they can handle messages.
    if ($workerId !== 0) {
        return;
    }

    // Wait (up to 5 seconds) until all the worker processes have started: shutting the pool down while a worker process
    // is still starting can leave that process running after the pool has stopped.
    for ($i = 0; $i < 500 && $started->get() < 3; $i++) {
        usleep(10_000);
    }

    $queue = msg_get_queue($key);
    if ($queue !== false) {
        for ($i = 1; $i <= 3; $i++) {
            msg_send($queue, 1, "Message #{$i}", false); // Send the message as it is, without serializing it.
        }
    }

    // Wait (up to 5 seconds) until all three messages have been handled.
    for ($i = 0; $i < 500 && $handled->get() < 3; $i++) {
        usleep(10_000);
    }

    $pool->shutdown();
});

$pool->start();

// A message queue stays in the system until it's removed, even after the pool has stopped. Remove it, so that no
// leftover messages are picked up by the next run.
$queue = msg_get_queue($key);
if ($queue !== false) {
    msg_remove_queue($queue);
}
