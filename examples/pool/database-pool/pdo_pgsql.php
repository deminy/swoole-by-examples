#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example demonstrates how to create and use a PostgreSQL connection pool in Swoole.
 *
 * We define a pool with a maximum of 100 connections (note: the default pool size is 64).
 * The script then performs 1,000 iterations, and in each iteration:
 *   - Acquires a connection from the pool.
 *   - Executes a simulated slow query that takes approximately 1 second.
 *   - Returns the connection back to the pool.
 *
 * Thanks to connection pooling and coroutine concurrency, the script completes
 * 1,000 sequential 1-second queries in just over 10 seconds (actual time may vary
 * depending on your hardware and runtime environment).
 *
 * You can use following command to run this script:
 *     docker compose exec -t client bash -c "./pool/database-pool/pdo_pgsql.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./pool/database-pool/pdo_pgsql.php"
 */

use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $config = (new PDOConfig())
        ->withDriver('pgsql')
        ->withHost('postgresql')
        ->withPort(5432)
        ->withUsername('username')
        ->withPassword('password')
        ->withDbName('test')
    ;
    $pool = new PDOPool($config, 100);

    for ($n = 1000; $n--;) {
        go(function () use ($pool): void {
            /** @var PDO $pdo */
            $pdo = $pool->get();

            $stmt = $pdo->prepare('SELECT pg_sleep(1)');
            if ($stmt === false) {
                echo 'Failed to prepare the statement.', PHP_EOL;
                return;
            }
            $stmt->execute(); // The query finishes in 1 second.
            $stmt->fetchAll();
            // The result array returned is:
            // [
            //     [
            //         'pg_sleep' => '',
            //          0 => '',
            //     ]
            // ];
            $stmt = null;

            $pool->put($pdo);
        });
    }
});

echo 'Done', PHP_EOL;
