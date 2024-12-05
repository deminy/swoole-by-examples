#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example is to show how to create coroutines using different functions/methods:
 *     1. use the procedural function swoole_coroutine_create().
 *     2. use the method in OOP style: Swoole\Coroutine::create().
 *     3. use function \Swoole\Coroutine\go() from Swoole Library.
 *     4. use the short name go().
 *     5. use the short name of class \Swoole\Coroutine to call the create() method: Co::create().
 *
 * The four function/method calls are of the same thing:
 *     1. Function swoole_coroutine_create() is the one which actually creates a coroutine.
 *     2. Method Swoole\Coroutine::create() is an alias of function swoole_coroutine_create().
 *     3. Function \Swoole\Coroutine\go() proxies the call to method Swoole\Coroutine::create().
 *     4. Function go() is a short name of function swoole_coroutine_create().
 *     5. "co" is a short name of class \Swoole\Coroutine (thus method co::create() works the same as method Swoole\Coroutine::create()).
 *
 * The script creates 5 coroutines; each takes about 1 second to finish. The script takes about 1 second to finish.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/creation-1.php"
 */

use function Swoole\Coroutine\run;

function test(int $i): void
{
    echo $i;
    co::sleep(1);
    // echo $i; // Uncomment this line to see the difference: The numbers could be printed out in a different order.
}

run(function (): void {
    swoole_coroutine_create('test', 1);

    // Recommended.
    Swoole\Coroutine::create('test', 2);

    // Recommended.
    Swoole\Coroutine\go('test', 3);

    // Recommended when short names are supported (i.e., when directive "swoole.use_shortname" is not explicitly turned off).
    go('test', 4);

    co::create('test', 5);

    echo PHP_EOL;
});

echo PHP_EOL;
