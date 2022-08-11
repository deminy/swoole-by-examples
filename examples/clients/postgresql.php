#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to interact with PostgreSQL.
 *
 * In this example, we make five PostgreSQL connections, and perform a three-second query in each connection. It takes
 * barely over three-second to run this script.
 *
 * The PostgreSQL client is added to Swoole in 5.0.0. This example won't work with old versions of Swoole.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/postgresql.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./clients/postgresql.php"
 */

use Swoole\Coroutine;
use Swoole\Coroutine\PostgreSQL;

Co\run(function () {
    $connections = [];
    for ($i = 0; $i < 5; $i++) {
        $connection = new PostgreSQL();
        $connection->connect('host=postgresql port=5432 dbname=test user=username password=password');
        $connections[] = $connection;
    }

    /** @var PostgreSQL $connection */
    foreach ($connections as $connection) {
        Coroutine::create(function () use ($connection) {
            $stmt = $connection->prepare('SELECT pg_sleep(3)');
            $stmt->execute(); // The query finishes in 3 seconds.
            $stmt->fetchAll();
            // The result array returned is:
            // [
            //     [
            //         'pg_sleep' => '',
            //     ]
            // ];
        });
    }
});
