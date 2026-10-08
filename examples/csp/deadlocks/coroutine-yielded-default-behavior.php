#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how a deadlock happens when the only coroutine yields its execution. There is no other coroutine
 * to execute, and the coroutine never gets resumed. Inside that coroutine, whatever code after the yield statement will
 * never be executed.
 *
 * A deadlock happens when every coroutine is paused, waiting for something that nothing can ever provide (e.g., data
 * from a channel that nobody will push to). Swoole detects this when the event loop has nothing left to do: it prints
 * a "[FATAL ERROR]: all coroutines (count: N) are asleep - deadlock!" message with a backtrace of each stuck coroutine,
 * then the script exits.
 *
 * This example shows deadlock information (the default behavior).
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/deadlocks/coroutine-yielded-default-behavior.php"
 */

use Swoole\Coroutine;

Coroutine::create(function (): void {
    echo '1', PHP_EOL; // This will be printed out.
    Coroutine::yield();
    echo '3', PHP_EOL; // This will never be printed out.
});
echo '2', PHP_EOL; // This will be printed out.

// NOTE: In most cases it's not necessary nor recommended to use method `Swoole\Event::wait()` directly in your code.
// The example in this file is just for demonstration purpose.
Swoole\Event::wait();
