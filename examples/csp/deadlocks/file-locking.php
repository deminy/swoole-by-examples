#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how deadlock happens when trying to lock a locked file while the existing lock never gets released.
 *
 * In this example,
 *   1. Runtime hooks are enabled by default. Thus, filesystem functions (e.g., fopen(), flock(), etc) are hooked, and
 *      they work in a coroutine-friendly style.
 *   2. The file is locked at line 28.
 *   3. When function flock() is called for a second time at line 33, it waits for the lock to be released, and the coroutine
 *      yields its execution. However, there isn't a second coroutine running, thus the existing lock will never be released.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/deadlocks/file-locking.php"
 */

use Swoole\Coroutine;

use function Swoole\Coroutine\run;

run(function (): void {
    echo '1', PHP_EOL; // This will be printed out.

    $filename = sys_get_temp_dir() . '/swoole-file-locking-' . uniqid() . '.tmp';

    $fp1 = fopen($filename, 'w');
    if ($fp1 === false) {
        throw new RuntimeException('Failed to open file.');
    }

    flock($fp1, LOCK_EX); // To acquire an exclusive lock (writer).

    echo '2', PHP_EOL; // This will be printed out.

    $fp2 = fopen($filename, 'w');
    if ($fp2 === false) {
        throw new RuntimeException('Failed to open file.');
    }

    flock($fp2, LOCK_EX); // Trying to acquire an exclusive lock (writer) again on the same file.

    // Whatever code you put here (within the coroutine) will never be executed.

    echo '4', PHP_EOL; // This will never be printed out.

    flock($fp1, LOCK_UN); // To release a lock.
    fclose($fp1);

    flock($fp2, LOCK_UN); // To release a lock.
    fclose($fp2);
});

echo '3', PHP_EOL; // This will be printed out.

// To clean up any temporary files created by this script.
register_shutdown_function(function (): void {
    shell_exec('rm -f ' . sys_get_temp_dir() . '/swoole-file-locking-*.tmp');
});
