#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example demonstrates how to configure and utilize different runtime hook flags in Swoole.
 *
 * Hook flags in Swoole allow certain blocking I/O operations (e.g., sleep, file operations, etc.)
 * to be made coroutine-friendly, enabling them to run non-blockingly within coroutines.
 *
 * Method \Swoole\Runtime::setHookFlags() changes the hooks for the whole process, effective immediately. Since go()
 * starts running the new coroutine right away, each sleep(1) call below runs under whatever hook flags were set just
 * before it.
 *
 * In this script:
 * - Five child coroutines are created within the main coroutine (initiated by `run()`).
 * - Each child coroutine sleeps for one second, but their execution behavior varies based on hook flag settings:
 *   1. The first three child coroutines run in non-blocking mode, since the sleep hook is enabled for them.
 *   2. The last two child coroutines run in blocking mode, since the sleep hook is disabled for them.
 *
 * Execution timing (about 3 seconds in total):
 * - The first three coroutines print 0, 1 and 2, and yield right away, since their sleep() calls are hooked.
 * - The fourth coroutine's sleep() call is not hooked, so it blocks the whole process for 1 second (it prints 3, then
 *   4), and so does the fifth one (it prints 5, then 6).
 * - Only then does Swoole's event loop get control again; the three hooked sleeps finish about 1 second later, and the
 *   three coroutines print 7.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/hook-flags.php"
 *
 * You can run the following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/hook-flags.php"
 *
 * The printed numbers in the output ("0123456777") illustrate the order of execution across different coroutines.
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Runtime;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// Enable all hook flags, making blocking I/O operations coroutine-friendly. This is already the default; it is set
// here only for clarity.
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
