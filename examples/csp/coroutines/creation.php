#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example is to show how to create coroutines in four different ways:
 *     1. use the procedural function swoole_coroutine_create().
 *     2. use the method in OOP style: Swoole\Coroutine::create().
 *     3. use the short name of class \Swoole\Coroutine to call the create() method: Co::create().
 *     4. use the short name go().
 *
 * This script creates 4 coroutines; each takes about 1 second to finish. The script takes about 1 second to finish.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/creation.php"
 */
function test(int $i)
{
    co::sleep(1);
    echo "Coroutine #{$i} is finishing execution.\n";
}

Co\run(function () {
    swoole_coroutine_create('test', 1);
    Swoole\Coroutine::create('test', 2);
    co::create('test', 3);
    go('test', 4);
});
