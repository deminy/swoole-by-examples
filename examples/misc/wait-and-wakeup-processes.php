#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to make one process wait (block) and have another process wake it up, using methods
 * \Swoole\Atomic::wait() and \Swoole\Atomic::wakeup() across separate OS processes.
 *
 * Class \Swoole\Atomic is backed by shared memory, so a single Atomic object created in the parent process is shared by
 * all child processes. This makes it possible for one process to block on the shared value while another process wakes
 * it up:
 *   - Method wait(float $timeout = 1.0) blocks the calling process while the value is 0. It returns true when another
 *     process wakes it up (consuming the value back from 1 to 0 in the same step), or false when the timeout expires.
 *     A timeout of -1 means to wait forever.
 *   - Method wakeup() sets the value from 0 to 1 and wakes up a process that is currently blocked in wait(). The wake
 *     only happens when the value is still 0: if the value has already been set to non-zero (e.g., via set()), wakeup()
 *     does nothing and the waiting process stays blocked. So don't mix set() into the wait()/wakeup() handshake — the
 *     value transitions are managed entirely by wait() and wakeup() themselves.
 *
 * The Atomic object MUST be created before the processes are started, so that both children share the same shared
 * memory. Note that class \Swoole\Atomic uses unsigned 32-bit integers and provides wait()/wakeup(), while class
 * \Swoole\Atomic\Long uses signed 64-bit integers but does NOT provide wait() nor wakeup().
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./misc/wait-and-wakeup-processes.php"
 */

use Swoole\Atomic;
use Swoole\Process;

// The Atomic object is created (with an initial value of 0) before any process is started, so that both the consumer
// and the producer share the same underlying shared memory.
$atomic = new Atomic();

// The consumer process blocks on wait() while the shared value is 0, waiting for the producer to wake it up.
$consumer = new Process(
    function () use ($atomic): void {
        echo '[consumer] Blocked, waiting for the producer to wake me up.', PHP_EOL;

        // wait(-1) blocks forever until another process calls wakeup(). It returns true once woken up.
        $atomic->wait(-1);

        // The shared value is back to 0 here: wakeup() set it from 0 to 1, and wait() consumed it back to 0.
        echo '[consumer] Woken up by the producer; shared value is back to ', $atomic->get(), '.', PHP_EOL;
        echo '[consumer] Done.', PHP_EOL;
    },
    false
);

// The producer process does some work and then wakes up the consumer.
$producer = new Process(
    function () use ($atomic): void {
        echo '[producer] Working for a moment before waking up the consumer.', PHP_EOL;
        sleep(1); // Simulate some work so that the consumer is already blocked in wait() by the time we wake it up.

        // wakeup() sets the shared value from 0 to 1 and wakes up the consumer blocked in wait(). Don't call set()
        // with a non-zero value first: wakeup() only performs the wake while the value is still 0, so pre-setting
        // the value would turn wakeup() into a no-op and leave the consumer blocked forever.
        $atomic->wakeup();
        echo '[producer] Woke up the consumer.', PHP_EOL;
        echo '[producer] Done.', PHP_EOL;
    },
    false
);

$consumer->start();
$producer->start();

// Reap both child processes in the parent to avoid leaving zombie processes behind.
for ($i = 0; $i < 2; $i++) {
    Process::wait();
}

echo '[parent] Both child processes have exited.', PHP_EOL;
