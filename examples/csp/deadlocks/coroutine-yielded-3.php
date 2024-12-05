#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when the only coroutine yields its execution. There is no any other coroutine
 * to execute, and the coroutine never gets resumed. Inside that coroutine, whatever code after the yield statement will
 * never be executed.
 *
 * This example sets a customized exit condition so that the program will finish its execution after all coroutines
 * finish execution. The program will never exit since the exit condition won't meet.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/deadlocks/coroutine-yielded-3.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

Coroutine::set(
    [
        Constant::OPTION_EXIT_CONDITION => function () {
            return Coroutine::stats()['coroutine_num'] === 0; // @phpstan-ignore offsetAccess.nonOffsetAccessible
        },
    ]
);
Coroutine::create(function (): void {
    echo '1', PHP_EOL; // This will be printed out.
    Coroutine::yield();
    echo '3', PHP_EOL; // This will never be printed out.
});
echo '2', PHP_EOL; // This will be printed out.

// NOTE: In most cases it's not necessary nor recommended to use method `Swoole\Event::wait()` directly in your code.
// The example in this file is just for demonstration purpose.
Swoole\Event::wait();
