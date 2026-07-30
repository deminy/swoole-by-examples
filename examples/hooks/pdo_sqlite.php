#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to interact with SQLite using the PDO_SQLITE driver.
 *
 * In this example, we make five SQLite connections, and perform a three-second query in each connection. It takes
 * barely over three seconds to run this script.
 *
 * Unlike MySQL and PostgreSQL, SQLite has no built-in sleep function, and custom SQLite functions can't be used to
 * add one either: the methods to register them (PDO::sqliteCreateFunction(), PDO::sqliteCreateAggregate(), and
 * PDO::sqliteCreateCollation()) have been removed from the coroutine environment since Swoole v6.1.5. To make a
 * query take three seconds, this example uses SQLite's own locking instead: a separate connection holds the write
 * lock on the database for the whole run, and each of the five connections then tries to write to the database with
 * a busy timeout (PDO::ATTR_TIMEOUT) of three seconds. Each query spends exactly three seconds blocked inside
 * SQLite's busy handler before giving up with a "database is locked" error - a genuinely slow SQLite call, which
 * Swoole executes without blocking other coroutines: the five three-second queries finish concurrently, not
 * serially.
 *
 * The PDO_SQLITE driver is supported in Swoole since v5.1.0, when Swoole is compiled with the --enable-swoole-sqlite
 * option. This example won't work with old versions of Swoole, or if Swoole is not compiled with the
 * --enable-swoole-sqlite option.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/pdo_sqlite.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/pdo_sqlite.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_PDO_SQLITE]);

// An in-memory SQLite database can't be shared between connections, so a temporary database file is used instead.
$file = tempnam(sys_get_temp_dir(), 'pdo_sqlite_');
if ($file === false) {
    throw new RuntimeException('Failed to create a temporary file for the SQLite database.');
}

// This connection is created outside of the coroutine environment, and holds the write lock on the database until
// the script ends, forcing the queries made in the coroutines below to wait for the lock.
$blocker = new PDO("sqlite:{$file}");
$blocker->exec('CREATE TABLE counters (value INTEGER)');
$blocker->exec('BEGIN IMMEDIATE'); // Acquire the write lock, and hold it (the transaction is never committed).

run(function () use ($file): void {
    for ($i = 0; $i < 5; $i++) {
        go(function () use ($file): void {
            $pdo = new PDO("sqlite:{$file}");
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 3); // Set the busy timeout of the connection to 3 seconds.

            try {
                // The write lock is never released, so this query keeps retrying inside SQLite's busy handler for 3
                // seconds, and then fails with a "database is locked" error.
                $pdo->exec('INSERT INTO counters VALUES (1)');
            } catch (PDOException) {
                // "SQLSTATE[HY000]: General error: 5 database is locked", as expected.
            }
        });
    }
});

$blocker = null;
unlink($file);
