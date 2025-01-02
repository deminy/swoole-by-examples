#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to interact with SQLite using the PDO_SQLITE driver.
 *
 * In this example, we make five SQLite connections, and perform a three-second query in each connection. It takes
 * barely over three seconds to run this script.
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

run(function (): void {
    for ($i = 0; $i < 5; $i++) {
        go(function (): void {
            // Here we use a temporary SQLite database for demonstration purpose. It will be deleted automatically when
            // the connection is closed.
            $pdo = new PDO('sqlite:');

            // Register a custom SQLite function to sleep for 3 seconds.
            $pdo->sqliteCreateFunction('sleepFor3Seconds', function (): void {
                sleep(3);
            });

            $pdo->exec('SELECT sleepFor3Seconds()');
        });
    }
});
