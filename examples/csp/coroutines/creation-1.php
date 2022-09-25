#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example is to show how to create coroutines using different functions/methods:
 *     1. use the method in OOP style: Swoole\Coroutine::create().
 *     2. use the short name go().
 *     3. use the procedural function swoole_coroutine_create().
 *     4. use the short name of class \Swoole\Coroutine to call the create() method: Co::create().
 *
 * The script creates 4 coroutines; each takes about 1 second to finish. The script takes about 1 second to finish.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/creation-1.php"
 */
function test(int $i): void
{
    echo $i;
    co::sleep(1);
    // echo $i; // Uncomment this line to see the difference: The numbers could be printed out in a different order.
}

Co\run(function () {
    // Recommended.
    Swoole\Coroutine::create('test', 1);

    // Recommended when short names are supported (i.e., when directive "swoole.use_shortname" is not explicitly turned off).
    go('test', 2);

    swoole_coroutine_create('test', 3);

    co::create('test', 4);

    echo "\n";
});

echo "\n";
