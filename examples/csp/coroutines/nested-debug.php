#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script is to help to understand how nested coroutines are executed in order; it works similarly to script
 * "nested.php" under the same directory. Once executed, it prints out "123456789".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/nested-debug.php"
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    go(function (): void {
        echo '1';
        go(function (): void {
            echo '2';
            sleep(3);
            echo '6';
            go(function (): void {
                echo '7';
                sleep(2);
                echo '9', PHP_EOL;
            });
            echo '8';
        });
        echo '3';
        sleep(1);
        echo '5';
    });
    echo '4';
});
