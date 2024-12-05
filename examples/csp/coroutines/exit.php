#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/exit.php"
 *
 * In lower versions of Swoole, exit() should not be used in coroutines since it results in unexpected behavior.
 * Starting from Swoole 4.1.0, you can use exit() inside coroutines directly. In this case, a \Swoole\ExitException
 * exception is thrown out instead of terminating code execution immediately.
 *
 * In general, the best way to exit from a coroutine is to throw out an exception and catch it at parent level.
 */

use function Swoole\Coroutine\run;

run(function (): void {
    try {
        exit(911);
    } catch (Swoole\ExitException $e) { // @phpstan-ignore catch.neverThrown
        echo <<<EOT
        Calling exit() inside a coroutine throws out a \\Swoole\\ExitException exception instead of terminating code execution
        directly.
        
        There are two extra methods in class \\Swoole\\ExitException::
        1. \\Swoole\\ExitException::getFlags(): The exit flags. In this example, the flags is {$e->getFlags()} (SWOOLE_EXIT_IN_COROUTINE).
        2. \\Swoole\\ExitException::getStatus(): The status as defined in PHP function exit(). In this example, the status is {$e->getStatus()}.

        EOT;
    }
});
