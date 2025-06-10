#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example demonstrates how to create and use a MySQL connection pool in Swoole.
 *
 * We define a pool with a maximum of 128 connections (note: the default pool size is 64).
 * The script then performs 1,024 iterations, and in each iteration:
 *   - Acquires a connection from the pool.
 *   - Executes a simulated slow query that takes approximately 1 second.
 *   - Returns the connection back to the pool.
 *
 * Thanks to connection pooling and coroutine concurrency, the script completes
 * 1,024 sequential 1-second queries in just over 8 seconds (actual time may vary
 * depending on your hardware and runtime environment).
 *
 * This pool is created using the mysqli extension; alternatively, you can use the PDO
 * MySQL extension.
 *
 * You can use following command to run this script:
 *     docker compose exec -t client bash -c "./pool/database-pool/mysqli.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./pool/database-pool/mysqli.php"
 */

use Swoole\Database\MysqliConfig;
use Swoole\Database\MysqliPool;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $config = (new MysqliConfig())
        ->withHost('mysql')
        ->withUsername('username')
        ->withPassword('password')
        ->withDbname('test')
    ;
    $pool = new MysqliPool($config, 128);

    for ($n = 1024; $n--;) {
        go(function () use ($pool): void {
            /** @var mysqli $mysqli */
            $mysqli = $pool->get();

            $stmt = $mysqli->prepare('SELECT SLEEP(1)');
            if ($stmt === false) {
                echo 'Failed to prepare the statement.', PHP_EOL;
                return;
            }
            $stmt->execute();
            $stmt->close();

            $pool->put($mysqli);
        });
    }
});

echo 'Done', PHP_EOL;
