#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows that a function which starts a coroutine returns as soon as that coroutine pauses, but only if
 * the coroutine pauses in a non-blocking way.
 *
 * The output "123456" is printed out in following order:
 *     * The digit "1" is printed out first.
 *     * After two seconds, next four digits "2345" are printed out.
 *     * After another two seconds, the last digit "6" is printed out.
 *
 * Notes:
 *     * Function blocking() executes in blocking mode, the same as in plain PHP.
 *     * Function nonBlocking() executes in non-blocking mode. It returns the string "5" before the nested coroutine
 *       inside it finishes.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/blocking-vs-non-blocking.php"
 */
use Swoole\Coroutine;

use function Swoole\Coroutine\go;

function blocking(): string
{
    go(function (): void {
        echo '1';
        // Although running inside a coroutine, the sleep() function call is still executed in blocking mode: runtime
        // hooks are off here, because this script doesn't use function Swoole\Coroutine\run().
        sleep(2);
        echo '2';
    });
    return '3';
}

function nonBlocking(): string
{
    go(function (): void {
        echo '4';
        Coroutine::sleep(2); // This is the non-blocking version of the sleep() function call.
        echo '6', PHP_EOL;
    });
    return '5';
}

echo blocking(), nonBlocking();

// NOTE: In most cases it's not necessary nor recommended to use method `Swoole\Event::wait()` directly in your code.
// The example in this file is just for demonstration purpose.
Swoole\Event::wait();
