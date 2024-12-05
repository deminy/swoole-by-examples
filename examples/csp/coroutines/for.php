#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script takes about 1 second to finish, with 2,000 coroutines created in a for loop. Without coroutine enabled,
 * this script takes about 2,000 seconds to finish.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/for.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/for.php"
 */
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    for ($i = 0; $i < 2_000; $i++) {
        go(function (): void {
            // Note that we use the PHP function sleep() directly.
            sleep(1);
        });
    }

    // Note that there are 2,001 coroutines created, including the main coroutine created by function call run().
    echo count(Coroutine::listCoroutines()), ' active coroutines when reaching the end of the PHP script.', PHP_EOL; // @phpstan-ignore argument.type
});
