#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how a deadlock happens when popping data from an empty channel.
 *
 * A deadlock happens when every coroutine is paused, waiting for something that nothing can ever provide (e.g., data
 * from a channel that nobody will push to). Swoole detects this when the event loop has nothing left to do: it prints
 * a "[FATAL ERROR]: all coroutines (count: N) are asleep - deadlock!" message with a backtrace of each stuck coroutine,
 * then the script exits.
 *
 * When deadlock information is hidden, the script still prints a warning like "channel is destroyed, 1 consumers will
 * be discarded" before exiting.
 *
 * How to run this script:
 *     # To show deadlock information, run either of the following commands:
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
