#!/usr/bin/env php
<?php
/**
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=app") bash -c "./for.php"
 *
 * This script takes about 1 second to finish. Without coroutine enabled (in line 11), this script takes about 2,000
 * seconds to finish.
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker exec -t  $(docker ps -qf "name=app") bash -c "time ./for.php"
 */

Swoole\Runtime::enableCoroutine();

for ($i = 1; $i <= 2000; $i++) {
    go(function () {
        sleep(1);
    });
}

echo count(Swoole\Coroutine::listCoroutines()), " active coroutines when reaching the end of the PHP script.\n";
