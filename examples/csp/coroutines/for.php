#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script takes about 1 second to finish, with 2,000 coroutines created. Without coroutine enabled (in line 14),
 * this script takes about 2,000 seconds to finish.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./csp/coroutines/for.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "time ./csp/coroutines/for.php"
 */
Swoole\Runtime::enableCoroutine();

for ($i = 1; $i <= 2000; $i++) {
    go(function () {
        sleep(1);
    });
}

echo count(Swoole\Coroutine::listCoroutines()), " active coroutines when reaching the end of the PHP script.\n";
