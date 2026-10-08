<?php

declare(strict_types=1);

/**
 * This example shows how threads share data through class \Swoole\Thread\Map, a key-value map that every thread can
 * read and write.
 *
 * Swoole 6 can run PHP code in multiple threads (class \Swoole\Thread), on a thread-safe (ZTS) build of PHP. Each
 * thread runs a PHP script with its own variables, so ordinary PHP arrays and objects are never shared between
 * threads. To share data, pass a thread-safe container to the threads when creating them: \Swoole\Thread\Map (used
 * here), \Swoole\Thread\ArrayList, \Swoole\Thread\Queue (see example "queue.php"), or \Swoole\Thread\Atomic.
 *
 * In this example, the main thread starts four threads, which all update the same map:
 *   1. Each thread increments counter "atomic" 10,000 times using method incr(), which reads and updates the value in
 *      one step. The result is always 40,000.
 *   2. Each thread also increments counter "unsafe" 10,000 times by reading the value and writing it back in two
 *      steps. Two threads can read the same value before either writes it back, so some increments get lost, and the
 *      result is less than 40,000, often by a lot.
 *   3. Each thread records its own entry in the map, e.g., "thread-1".
 *
 * This example requires a ZTS build of PHP, so it runs in the "zts" container (the official ZTS image of Swoole).
 *
 * How to run this script:
 *     docker compose exec -t zts php ./threads/map.php
 */

use Swoole\Thread;
use Swoole\Thread\Map;

$args = Thread::getArguments();
if (!isset($args)) { // The main thread.
    $map            = new Map();
    $map['atomic']  = 0;
    $map['unsafe']  = 0;

    $threads = [];
    for ($i = 1; $i <= 4; $i++) {
        // The script file to run in the new thread, followed by the arguments passed to it.
        $threads[] = new Thread(__FILE__, $i, $map);
    }
    foreach ($threads as $thread) {
        $thread->join(); // Wait for the thread to finish.
    }

    echo 'Counter "atomic" (updated with incr()): ', $map['atomic'], PHP_EOL;
    echo 'Counter "unsafe" (read, then written back): ', $map['unsafe'], ' (40,000 only if no update was lost)', PHP_EOL;
    $entries = array_filter($map->keys(), fn ($key) => str_starts_with((string) $key, 'thread-'));
    sort($entries);
    echo 'Entries written by the threads: ', implode(', ', $entries), PHP_EOL;
} else { // A child thread.
    [$id, $map] = $args;

    for ($i = 0; $i < 10_000; $i++) {
        $map->incr('atomic', 1);
        $map['unsafe'] = $map['unsafe'] + 1;
    }
    $map["thread-{$id}"] = 'done';
}
