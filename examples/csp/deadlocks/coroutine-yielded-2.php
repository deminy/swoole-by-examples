#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when the only coroutine yields its execution. There is no any other coroutine
 * to execute, and the coroutine never gets resumed. Inside that coroutine, whatever code after the yield statement will
 * never be executed.
 *
 * This example hides deadlock information by setting the `enable_deadlock_check` option to `false`.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/deadlocks/coroutine-yielded-2.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

Coroutine::set([Constant::OPTION_ENABLE_DEADLOCK_CHECK => false]);
Coroutine::create(function (): void {
    echo '1', PHP_EOL; // This will be printed out.
    Coroutine::yield();
    echo '3', PHP_EOL; // This will never be printed out.
});
echo '2', PHP_EOL; // This will be printed out.

// NOTE: In most cases it's not necessary nor recommended to use method `Swoole\Event::wait()` directly in your code.
// The example in this file is just for demonstration purpose.
Swoole\Event::wait();
