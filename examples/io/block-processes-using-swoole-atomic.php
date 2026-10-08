#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block a process using method \Swoole\Atomic::wait(), and how to wake it up from
 * another process using method \Swoole\Atomic::wakeup(). Method wait() blocks the whole process, including any other
 * coroutines in it, unlike a coroutine sleep (see example "block-a-coroutine.php").
 *
 * Class \Swoole\Atomic is backed by shared memory, so an Atomic object created before the child processes are started
 * is shared by all of them:
 *   - Method wait(float $timeout = 1.0) blocks the calling process while the value is 0. It returns true once another
 *     process calls wakeup() (wait() then sets the value back from 1 to 0), or false when the timeout expires. A
 *     timeout of -1 means to wait forever.
 *   - Method wakeup() sets the value from 0 to 1 and wakes up the blocked process. It does nothing if the value is
 *     already non-zero (e.g., after a call to set()), so don't mix set() into the wait()/wakeup() handshake.
 *   - Method wakeup(int $count) can unblock several processes that are already waiting, but it still stores a single
 *     signal, not a count: only one of their wait() calls returns true, and the others return false.
 *
 * Only class \Swoole\Atomic provides wait() and wakeup(); class \Swoole\Atomic\Long does not.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-processes-using-swoole-atomic.php"
 *
 * There are other ways to block processes. Check following examples to see how to use class \Swoole\Lock to do it:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-process-using-swoole-lock.php
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-lock.php
 */

use Swoole\Atomic;
use Swoole\Process;

// The Atomic object (with an initial value of 0) must be created before the child processes are started, so that they
// share the same value.
$atomic = new Atomic();

// The consumer process is blocked twice: first until a timeout expires, then until the producer wakes it up.
$consumer = new Process(
    function () use ($atomic): void {
        $result = $atomic->wait(0.1); // Nobody wakes the consumer up within 0.1 second, so wait() returns false.
        echo '[consumer] Blocked for 0.1 second; wait() returned ', var_export($result, true), '.', PHP_EOL;

        echo '[consumer] Blocked again, waiting for the producer to wake me up.', PHP_EOL;
        $result = $atomic->wait(-1); // Blocks until another process calls wakeup().
        echo '[consumer] Woken up; wait() returned ', var_export($result, true), '.', PHP_EOL;
        // wakeup() set the value from 0 to 1, and wait() set it back to 0.
        echo '[consumer] The value is back to ', $atomic->get(), '.', PHP_EOL;
    },
    false
);

// The producer process wakes up the consumer.
$producer = new Process(
    function () use ($atomic): void {
        // Used only to better order the output. Calling wakeup() before the consumer calls wait() works too: the value
        // stays at 1, and the next call to wait() returns true right away.
        sleep(1);
        echo '[producer] Waking up the consumer.', PHP_EOL;
        $atomic->wakeup();
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
