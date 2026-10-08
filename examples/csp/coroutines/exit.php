#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/exit.php"
 *
 * When exit() is called inside a coroutine, a \Swoole\ExitException exception is thrown out instead of terminating
 * code execution immediately.
 *
 * An exception thrown inside a coroutine can only be caught inside that same coroutine; it is NOT passed on to the
 * coroutine that created it. If it's not caught there, the whole process stops with a fatal error. The same applies to
 * the \Swoole\ExitException thrown by exit(): if it's not caught, the script ends with "Fatal error: Uncaught
 * Swoole\ExitException" and exit status 255, not the status passed to exit(). So to stop a coroutine early, return
 * from its function (or throw an exception and catch it at the top of that coroutine's function).
 */

use function Swoole\Coroutine\run;

run(function (): void {
    try {
        exit(911);
    } catch (Swoole\ExitException $e) { // @phpstan-ignore catch.neverThrown
        echo <<<EOT
        Calling exit() inside a coroutine throws out a \\Swoole\\ExitException exception instead of terminating code execution
        directly.
        
        There are two extra methods in class \\Swoole\\ExitException:
        1. \\Swoole\\ExitException::getFlags(): The exit flags. In this example, the flags value is {$e->getFlags()} (SWOOLE_EXIT_IN_COROUTINE).
        2. \\Swoole\\ExitException::getStatus(): The status as defined in PHP function exit(). In this example, the status is {$e->getStatus()}.

        EOT;
    }
});
