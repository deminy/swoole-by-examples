#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script takes about 1 second to finish, with 2,000 coroutines created. Without coroutine enabled (in line 19),
 * this script takes about 2,000 seconds to finish.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./csp/coroutines/for.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "time ./csp/coroutines/for.php"
 */
use Swoole\Constant;
use Swoole\Coroutine;

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_ALL]);

for ($i = 1; $i <= 2_000; $i++) {
    Coroutine::create(function () {
        sleep(1);
    });
}

echo count(Coroutine::listCoroutines()), " active coroutines when reaching the end of the PHP script.\n";
