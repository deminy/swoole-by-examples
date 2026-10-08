<?php

declare(strict_types=1);

/**
 * This example shows how to hand out work to a fixed number of worker threads through class \Swoole\Thread\Queue, a
 * first-in-first-out queue that every thread can push to and pop from.
 *
 * A queue shared by a few long-running worker threads is the basic shape of a thread pool: the threads are created
 * once, and each one keeps taking the next job from the queue until there is no more work. Method pop() can wait for
 * the next item; when an item is pushed with Queue::NOTIFY_ONE, one waiting thread is woken up to take it. Without a
 * notify flag (Queue::NOTIFY_ONE or Queue::NOTIFY_ALL), method push() doesn't wake a thread that is already waiting in
 * pop(-1), so that thread would wait forever. (Method pop() without an argument doesn't wait at all: it returns null
 * right away when the queue is empty.)
 *
 * In this example:
 *   1. The main thread starts three worker threads, sharing two queues with them: one for jobs, one for results.
 *   2. The main thread pushes nine jobs (numbers to square) to the job queue, followed by one "stop" marker for each
 *      worker thread.
 *   3. Each worker thread pops jobs until it gets a stop marker, and pushes each result to the result queue.
 *   4. The main thread collects the nine results. Which worker handles which job depends on timing.
 * To stay minimal, this example doesn't handle a worker thread that dies (e.g., on an uncaught exception): the main
 * thread would then wait forever for the missing result. A real thread pool must handle that, e.g., by catching
 * exceptions in the worker and pushing an error result instead.
 *
 * This example requires a ZTS build of PHP, so it runs in the "zts" container (the official ZTS image of Swoole).
 *
 * How to run this script:
 *     docker compose exec -t zts php ./threads/queue.php
 */

use Swoole\Thread;
use Swoole\Thread\Queue;

const WORKERS = 3;
const JOBS    = 9;
const STOP    = 'stop';

$args = Thread::getArguments();
if (!isset($args)) { // The main thread.
    $jobs    = new Queue();
    $results = new Queue();

    $threads = [];
    for ($i = 1; $i <= WORKERS; $i++) {
        $threads[] = new Thread(__FILE__, $i, $jobs, $results);
    }

    for ($n = 1; $n <= JOBS; $n++) {
        $jobs->push($n, Queue::NOTIFY_ONE); // @phpstan-ignore classConstant.notFound
    }
    for ($i = 1; $i <= WORKERS; $i++) {
        $jobs->push(STOP, Queue::NOTIFY_ONE); // @phpstan-ignore classConstant.notFound
    }

    $squares = [];
    for ($n = 1; $n <= JOBS; $n++) {
        /** @var array{int, int, int} $result */
        $result                     = $results->pop(-1); // Wait (forever, if needed) for the next result.
        [$worker, $number, $square] = $result;
        echo "Worker thread #{$worker} squared {$number}: {$square}", PHP_EOL;
        $squares[$number] = $square;
    }
    foreach ($threads as $thread) {
        $thread->join();
    }

    ksort($squares);
    echo 'All results, in order: ', implode(', ', $squares), PHP_EOL;
} else { // A worker thread.
    [$id, $jobs, $results] = $args;

    while (($job = $jobs->pop(-1)) !== STOP) {
        usleep(10_000); // To simulate some work.
        $results->push([$id, $job, $job * $job], Queue::NOTIFY_ONE); // @phpstan-ignore classConstant.notFound
    }
}
