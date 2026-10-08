#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we make five concurrent MySQL connections using the mysqli extension, each running one query.
 * Each query takes three seconds to finish. Run one after another (blocking), the five queries would take 15 seconds.
 * Since Swoole runs them concurrently in five coroutines, the script finishes in barely over three seconds.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/mysqli.php"
 *
 * You can run the following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/mysqli.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// This statement is optional because hook flags are set to SWOOLE_HOOK_ALL by default, and flag SWOOLE_HOOK_ALL has
// flag SWOOLE_HOOK_TCP included already.
Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_TCP]);

run(function (): void {
    for ($i = 0; $i < 5; $i++) {
        go(function (): void {
            $mysqli = new mysqli('mysql', 'username', 'password', 'test');
            $stmt   = $mysqli->prepare('SELECT SLEEP(3)');
            if ($stmt === false) {
                echo 'Failed to prepare the statement.', PHP_EOL;
                return;
            }

            $stmt->execute();
            $stmt->close();
            $mysqli->close();
        });
    }
});
