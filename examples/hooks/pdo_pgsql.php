#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to interact with PostgreSQL using the PDO_PGSQL driver.
 *
 * In this example, we make five PostgreSQL connections, and perform a three-second query in each connection. It takes
 * barely over three seconds to run this script.
 *
 * The PDO_PGSQL driver is supported in Swoole since v5.1.0, when Swoole is compiled with the --enable-swoole-pgsql
 * option. This example won't work with old versions of Swoole, or if Swoole is not compiled with the
 * --enable-swoole-pgsql option.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/pdo_pgsql.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/pdo_pgsql.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// This statement is optional because hook flags are set to SWOOLE_HOOK_ALL by default, and flag SWOOLE_HOOK_ALL has
// flag SWOOLE_HOOK_PDO_PGSQL included already.
Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_PDO_PGSQL]);

run(function (): void {
    for ($i = 0; $i < 5; $i++) {
        go(function (): void {
            $pdo  = new PDO('pgsql:host=postgresql;port=5432;dbname=test', 'username', 'password');
            $stmt = $pdo->prepare('SELECT pg_sleep(3)');
            if ($stmt === false) {
                echo 'Failed to prepare the statement.', PHP_EOL;
                return;
            }

            $stmt->execute(); // The query finishes in 3 seconds.
            $stmt->fetchAll();
            // The result array returned is:
            // [
            //     [
            //         'pg_sleep' => '',
            //          0 => '',
            //     ]
            // ];
            $stmt = null;
            $pdo  = null;
        });
    }
});
