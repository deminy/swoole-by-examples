#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example compares the return statements used in blocking function calls and non-blocking function calls.
 *
 * The output "123456" is printed out in following order:
 *     * The digit "1" is printed out first.
 *     * After one second, next four digits "2345" are printed out.
 *     * After another second, the last digit "6" is printed out.
 *
 * Notes:
 *     * Function blocking() executes in blocking mode, just like what we used to see.
 *     * Function nonBlocking() executes in non-blocking mode. It returns an integer value "5" back first before
 *       finishing executing the nested coroutine inside it.
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
        sleep(2); // Although running inside a coroutine, the sleep() function call is still executed in blocking mode (when not hooked).
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
