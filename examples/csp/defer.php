#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example is to show how defer works in Swoole. It takes about 1 second to finish, and prints out "12345678".
 *
 * Function defer() registers a callback that runs when the current coroutine finishes. Several deferred callbacks run
 * in reverse order (last registered, first run), which is why "7" is printed after "6". Like go(), defer() is a short
 * name of function \Swoole\Coroutine\defer().
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/defer.php"
 */
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    go(function (): void {
        echo '1';
        defer(function (): void {
            echo '7';
        });

        echo '2';
        defer(function (): void {
            echo '6';
        });

        echo '3';
        Coroutine::sleep(1);
        echo '5';
    });
    echo '4';
});
echo '8', PHP_EOL;
