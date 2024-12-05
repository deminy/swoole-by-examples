#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./io/non-blocking-io-debug.php"
 *
 * This script takes about 2 seconds to finish, and prints out "123456".
 *
 * Here the Swoole function co:sleep() is used to simulate non-blocking I/O. If we update the code to make it work in
 * blocking mode, it takes about 3 seconds to finish, as you can see in script "blocking-io.php".
 *
 * For a simple version of this script, please check script "non-blocking-io.php".
 */

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    go(function (): void {
        echo '1';
        sleep(2);
        echo '6';
    });
    echo '2';
    go(function (): void {
        echo '3';
        sleep(1);
        echo '5';
    });
    echo '4';
});
