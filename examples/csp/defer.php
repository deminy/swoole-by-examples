#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example is to show how defer works in Swoole. It takes about 1 second to finish, and prints out "12345678".
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
