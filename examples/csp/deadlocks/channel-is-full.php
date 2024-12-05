#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when pushing data to a full channel.
 *
 * How to run this script:
 *     # To show deadlock information, run following command:
 *     docker compose exec -t client bash -c "./csp/deadlocks/channel-is-full.php"
 *     docker compose exec -t client bash -c "./csp/deadlocks/channel-is-full.php 1"
 *
 *     # To hide deadlock information, run following command:
 *     docker compose exec -t client bash -c "./csp/deadlocks/channel-is-full.php 0"
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
        $channel = new Channel(1); // Size of the channel is 1.
        echo '1', PHP_EOL; // This will be printed out.
        $channel->push('foo');
        echo '2', PHP_EOL; // This will be printed out.
        $channel->push('bar'); // No room to store it in the channel.
        echo '4', PHP_EOL; // This will never be printed out.
    });
    echo '3', PHP_EOL; // This will be printed out.
});
