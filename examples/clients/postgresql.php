#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to interact with PostgreSQL.
 *
 * The PostgreSQL client is added to Swoole in 5.0.0. This example won't work with old versions of Swoole.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./clients/postgresql.php"
 */

use Swoole\Coroutine\PostgreSQL;

Co\run(function () {
    $db   = new PostgreSQL();
    $res  = $db->connect('host=postgresql port=5432 dbname=test user=username password=password');
    $stmt = $db->prepare('SELECT pg_sleep(3)');
    $stmt->execute(); // The query finishes in 3 seconds.
    $stmt->fetchAll();
    // The result array returned is:
    // [
    //     [
    //         'pg_sleep' => '',
    //     ]
    // ];
});
