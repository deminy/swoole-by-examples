#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when popping data from an empty channel.
 *
 * How to run this script:
 *     # To show deadlock information, run following command:
 *     docker compose exec -t client bash -c "./csp/deadlocks/an-empty-channel.php"
 *     docker compose exec -t client bash -c "./csp/deadlocks/an-empty-channel.php 1"
 *
 *     # To hide deadlock information, run following command:
 *     docker compose exec -t client bash -c "./csp/deadlocks/an-empty-channel.php 0"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

use function Swoole\Coroutine\run;

Coroutine::set(
    [
        Constant::OPTION_ENABLE_DEADLOCK_CHECK => (bool) ($argv[1] ?? true),
    ]
);

run(function (): void {
    Coroutine::create(function (): void {
        echo '1', PHP_EOL; // This will be printed out.
        (new Channel(1))->pop();
        echo '3', PHP_EOL; // This will never be printed out.
    });
    echo '2', PHP_EOL; // This will be printed out.
});
