#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example is to show how to create coroutines in four different ways:
 *     1. use procedural function.
 *     2. use method in OOP.
 *     3. use short name.
 *     4. use alias.
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

swoole_coroutine_create('test', 1);
Swoole\Coroutine::create('test', 2);
co::create('test', 3);
go('test', 4);

Swoole\Event::wait();
