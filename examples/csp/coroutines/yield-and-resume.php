#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example is to show how to yield and resume coroutines. It takes about 1 second to finish, and prints out
 * "12345678".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/yield-and-resume.php"
 */
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $cid = go(function (): void {
        echo '1';
        Coroutine::yield();
        echo '6';
    });

    echo '2';

    go(function () use ($cid): void {
        echo '3';
        Coroutine::sleep(1);
        echo '5';
        Coroutine::resume($cid);
        echo '7';
    });

    echo '4';
});
echo '8', PHP_EOL;
