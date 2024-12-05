#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./io/non-blocking-io.php"
 *
 * This script takes about 2 seconds to finish, and prints out "21".
 *
 * Here the Swoole function co:sleep() is used to simulate non-blocking I/O. If we update the code to make it work in
 * blocking mode, it takes about 3 seconds to finish, as you can see in script "blocking-io.php".
 *
 * To see how the code is executed in order, please check script "non-blocking-io-debug.php".
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    go(function (): void {
        sleep(2);
        echo '1';
    });

    go(function (): void {
        sleep(1);
        echo '2';
    });
});
