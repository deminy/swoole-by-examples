#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example demonstrates how to configure and utilize different runtime hook flags in Swoole.
 *
 * Hook flags in Swoole allow certain blocking I/O operations (e.g., sleep, file operations, etc.)
 * to be made coroutine-friendly, enabling them to run non-blockingly within coroutines.
 *
 * In this script:
 * - Five sub-coroutines are created within the main coroutine (initiated by `run()`).
 * - Each sub-coroutine sleeps for one second, but their execution behavior varies based on hook flag settings:
 *   1. The first three sub-coroutines run in non-blocking mode due to specific hook flags being set.
 *   2. The last two sub-coroutines run in blocking mode because certain hook flags are disabled.
 *
 * Execution timing:
 * - The script takes approximately 3 seconds to complete.
 * - Sub-coroutines execute as follows:
 *   1. The first three (non-blocking) sub-coroutines complete last but run concurrently, taking ~1 second.
 *   2. The fourth (blocking) sub-coroutine finishes first, taking ~1 second.
 *   3. The fifth (blocking) sub-coroutine finishes second, taking ~1 second.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/hook-flags.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/hook-flags.php"
 *
 * The printed numbers in the output illustrate the order of execution across different coroutines.
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Runtime;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// Globally enable all hook flags to make blocking I/O operations coroutine-friendly by default.
Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_ALL]);

run(function (): void {
    // Enable the hook for sleep-related functions, allowing them to run non-blockingly.
    Runtime::setHookFlags(SWOOLE_HOOK_SLEEP);
    go(function (): void {
        echo '0';
        sleep(1);
        echo '7';
    });

    // Enable hooks for sleep, file, and process-related functions, making them coroutine-friendly.
    Runtime::setHookFlags(SWOOLE_HOOK_SLEEP | SWOOLE_HOOK_FILE | SWOOLE_HOOK_PROC);
    go(function (): void {
        echo '1';
        sleep(1);
        echo '7';
    });

    // Enable all hooks, making all supported blocking I/O operations coroutine-friendly.
    Runtime::setHookFlags(SWOOLE_HOOK_ALL);
    go(function (): void {
        echo '2';
        sleep(1);
        echo '7';
    });

    // Enable all hooks except for sleep-related ones. Sleep operations will now block.
    Runtime::setHookFlags(SWOOLE_HOOK_ALL ^ SWOOLE_HOOK_SLEEP);
    go(function (): void {
        echo '3';
        sleep(1);
        echo '4';
    });

    // Disable all hook flags, causing all I/O operations to run in blocking mode.
    Runtime::setHookFlags(0);
    go(function (): void {
        echo '5';
        sleep(1);
        echo '6';
    });
});

echo PHP_EOL;
