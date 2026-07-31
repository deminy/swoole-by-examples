#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows the different ways to enable and disable Swoole's coroutine support (runtime hooks) in a
 * standalone process.
 *
 * "Enabling coroutines" covers two independent things:
 * 1. Creating a coroutine context: `Swoole\Coroutine\run()`, `go()`, `Swoole\Coroutine::create()`, etc. This is
 *    covered by examples "creation-1.php" and "creation-2.php" in the same folder.
 * 2. Enabling runtime hooks: a global per-process bitmask that turns blocking PHP functions (e.g., the plain
 *    `sleep()`) into non-blocking, coroutine-friendly versions. This is what this example is about.
 *
 * The ways to enable/disable runtime hooks in a standalone process:
 * 1. Implicitly: `Swoole\Coroutine\run()` enables ALL runtime hooks by itself, unless hook flags were already
 *    set beforehand. This is why most examples in this repository work without ever mentioning hooks.
 * 2. Explicitly: `Swoole\Runtime::setHookFlags($flags)` (applied immediately), or its alias
 *    `Swoole\Runtime::enableCoroutine($flags)` (identical, except the argument defaults to SWOOLE_HOOK_ALL), or
 *    `Swoole\Coroutine::set(['hook_flags' => $flags])` (recorded immediately, applied when a scheduler starts).
 *    The bitmask can be the global SWOOLE_HOOK_ALL, a single SWOOLE_HOOK_* flag, or several flags combined with
 *    bitwise operators; only the functions covered by the selected flags get hooked.
 * 3. Disabling: pass 0 as the hook flags, e.g., `Swoole\Runtime::setHookFlags(0)`. Once hook flags have been
 *    set explicitly (even to 0), `Swoole\Coroutine\run()` respects that choice and no longer overrides it.
 *
 * NOTES:
 * 1. Since Swoole 6.0, `Runtime::enableCoroutine()` accepts an integer bitmask only. The boolean form from
 *    Swoole 4/5 was removed: `Runtime::enableCoroutine(true)` now coerces to the unused bit 1 and enables
 *    NOTHING (or throws a TypeError under strict types). Always pass SWOOLE_HOOK_* constants (or nothing).
 * 2. `Runtime::setHookFlags()` and `Runtime::enableCoroutine()` work in PHP CLI mode only.
 * 3. For more on combining and narrowing down hook flags (and on per-driver flags like SWOOLE_HOOK_CURL),
 *    please check example "hooks/hook-flags.php" and the other examples under "hooks/".
 * 4. To see how to enable coroutines in a server environment, please check example
 *    "servers/enable-coroutine.php".
 *
 * In this script, the same workload (two coroutines each calling the plain PHP `sleep(1)`) is run five times
 * under different hook flags. Whenever the sleep hook is on, the two coroutines sleep concurrently and print
 * "1212" in about 1 second; whenever it is off, sleep() blocks the whole process, so the coroutines run one
 * after the other and print "1122" in about 2 seconds.
 * - Step 1 shows that a fresh PHP process starts with no runtime hooks enabled.
 * - Step 2 (enabled, implicitly): `run()` enables all runtime hooks by itself: "1212".
 * - Step 3 (disabled): the hook flags persist after `run()` returns, and are turned off with
 *   `Runtime::setHookFlags(0)`. `run()` respects the explicit choice: "1122".
 * - Step 4 (enabled selectively, sleep hook excluded): two hook flags combined with a bitwise OR, but the sleep
 *   hook is not among them, so sleep() still blocks: "1122".
 * - Step 5 (enabled selectively, sleep hook included): only the sleep hook is enabled; sleep() is non-blocking
 *   again while every other blocking function remains unhooked: "1212".
 * - Step 6 (enabled globally): `Runtime::enableCoroutine()` turns all runtime hooks back on: "1212".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/enable-and-disable.php"
 *
 * You can run following command to see how much time it takes to run the script (about 7 seconds in total):
 *     docker compose exec -t client bash -c "time ./csp/coroutines/enable-and-disable.php"
 */

use Swoole\Runtime;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// The shared workload: two coroutines, each printing its number, sleeping for 1 second using the plain PHP
// sleep(), then printing its number again. With the sleep hook enabled the coroutines sleep concurrently
// ("1212" printed in about 1 second); with the sleep hook disabled sleep() blocks the whole process, so the
// coroutines run one after the other ("1122" printed in about 2 seconds).
function twoCoroutinesSleeping(): void
{
    for ($i = 1; $i <= 2; $i++) {
        go(function () use ($i): void {
            echo $i;
            sleep(1);
            echo $i;
        });
    }
}

// Step 1: a fresh PHP process starts with no runtime hooks enabled.
echo 'Hook flags in a fresh process: ', Runtime::getHookFlags(), PHP_EOL;

// Step 2 (enabled, implicitly): run() enables all runtime hooks by itself.
run(function (): void {
    echo 'Hook flags inside run() match SWOOLE_HOOK_ALL: ', (Runtime::getHookFlags() === SWOOLE_HOOK_ALL) ? 'true' : 'false', PHP_EOL;
    twoCoroutinesSleeping();
});
echo PHP_EOL;

// Step 3 (disabled): hook flags are global and per-process; they persist after run() returns. Disable them all
// by setting the bitmask to 0. Since the hook flags have now been set explicitly, run() no longer enables hooks
// by itself.
echo 'Hook flags persist after run() returns: ', (Runtime::getHookFlags() === SWOOLE_HOOK_ALL) ? 'true' : 'false', PHP_EOL;
Runtime::setHookFlags(0);
echo 'Hook flags after Runtime::setHookFlags(0): ', Runtime::getHookFlags(), PHP_EOL;
run('twoCoroutinesSleeping');
echo PHP_EOL;

// Step 4 (enabled selectively, sleep hook excluded): individual hook flags can be combined with bitwise
// operators. Here TCP and file operations are hooked, but sleep-related functions are not, so sleep() still
// blocks: only the selected functions get hooked.
Runtime::setHookFlags(SWOOLE_HOOK_TCP | SWOOLE_HOOK_FILE);
echo 'Hook flags SWOOLE_HOOK_TCP | SWOOLE_HOOK_FILE include the sleep hook: ', ((Runtime::getHookFlags() & SWOOLE_HOOK_SLEEP) !== 0) ? 'true' : 'false', PHP_EOL;
run('twoCoroutinesSleeping');
echo PHP_EOL;

// Step 5 (enabled selectively, sleep hook included): with only the sleep hook enabled, sleep() is non-blocking
// again, while every other blocking function remains unhooked.
Runtime::setHookFlags(SWOOLE_HOOK_SLEEP);
echo 'Hook flags SWOOLE_HOOK_SLEEP include the sleep hook: ', ((Runtime::getHookFlags() & SWOOLE_HOOK_SLEEP) !== 0) ? 'true' : 'false', PHP_EOL;
run('twoCoroutinesSleeping');
echo PHP_EOL;

// Step 6 (enabled globally): turn all runtime hooks back on. enableCoroutine() is an alias of setHookFlags(),
// with the argument defaulting to SWOOLE_HOOK_ALL; the call below is the same as
// Runtime::enableCoroutine(SWOOLE_HOOK_ALL) or Runtime::setHookFlags(SWOOLE_HOOK_ALL).
Runtime::enableCoroutine();
echo 'Hook flags after Runtime::enableCoroutine() match SWOOLE_HOOK_ALL: ', (Runtime::getHookFlags() === SWOOLE_HOOK_ALL) ? 'true' : 'false', PHP_EOL;
run('twoCoroutinesSleeping');
echo PHP_EOL;
