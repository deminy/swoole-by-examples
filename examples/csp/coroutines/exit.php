#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./csp/coroutines/exit.php"
 *
 * In lower versions of Swoole, exit() should not be used in coroutines since it results in unexpected behavior.
 * Starting from Swoole 4.1.0, you can use exit() inside coroutines directly. In this case, a \Swoole\ExitException
 * exception is thrown out instead of terminating code execution immediately.
 *
 * In general, a best way to to exit from a coroutine is to throw out an exception and catch it at parent level.
 */
go(function () {
    try {
        exit();
    } catch (Swoole\ExitException $e) {
        echo <<<EOT
        Calling exit() inside a coroutine throws out a \\Swoole\\ExitException exception instead
        of terminating code execution directly.\n
        EOT;
    }
});
