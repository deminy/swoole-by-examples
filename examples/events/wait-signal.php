#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to wait for signals in a blocking, coroutine-friendly way, using method
 * \Swoole\Coroutine\System::waitSignal().
 *
 * Examples "default-exit-condition.php" and "customized-exit-condition.php" handle signals in a callback
 * style: a listener is registered, and a handler function runs whenever the signal arrives. Method
 * \Swoole\Coroutine\System::waitSignal() offers a different, synchronous style: the calling coroutine simply
 * blocks until the given signal arrives (or a timeout expires), and the code then continues right where it was
 * waiting - no callbacks involved. Only the calling coroutine is suspended; other coroutines in the same
 * process keep running in the meantime.
 *
 * In this script:
 * 1. A child process is created; inside it, a coroutine waits for a SIGUSR1 signal, without a timeout.
 * 2. The parent process sends a SIGUSR1 signal to the child process 1 second later; the wait in the child
 *    process returns the number of the signal received, and the child process continues. (Since Swoole 6.0.0
 *    an array of signal numbers can be waited on at once, which is when the return value matters: it tells
 *    which one of the signals arrived.)
 * 3. The child process then waits for another SIGUSR1 signal, this time with a 0.5-second timeout. No further
 *    signal is sent, so the wait returns false once the timeout expires.
 *
 * The whole script takes about 1.5 seconds to finish.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./events/wait-signal.php"
 */

use Swoole\Coroutine\System;
use Swoole\Process;

use function Swoole\Coroutine\run;

// NOTE: the child process is created before any coroutine exists, since method Process::start() cannot be
// called from inside a coroutine.
$process = new Process(function (): void {
    run(function (): void {
        echo 'The child process is waiting for a SIGUSR1 signal.', PHP_EOL;
        // Block the current coroutine until a SIGUSR1 signal arrives (no timeout). The method returns the
        // number of the signal received (or false on timeout); an array of signal numbers can also be passed
        // to wait for any one of several signals (Swoole 6.0.0+).
        $received = System::waitSignal(SIGUSR1);
        echo 'Signal received: ', var_export($received, true), ' (SIGUSR1).', PHP_EOL;

        // When a timeout (in seconds) is given and no signal arrives in time, the method returns false.
        $received = System::waitSignal(SIGUSR1, 0.5);
        echo 'Signal received before the 0.5-second timeout: ', var_export($received, true), PHP_EOL;
    });
});
$process->start();

sleep(1); // Give the child process a moment to start waiting, making the output order deterministic.
Process::kill($process->pid, SIGUSR1);

Process::wait(); // Reap the child process.
