#!/usr/bin/env php
<?php

/**
 * In this example, we make five concurrent MySQL connection/queries using the mysqli extension.
 * Each query takes three seconds to finish. In non-blocking mode, it takes 15 seconds to make the five queries.
 * However, since the queries are executed asynchronously in Swoole, it takes barely over three seconds to finish all
 * the queries.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./hooks/mysqli.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "time ./hooks/mysqli.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_TCP]);

co\run(function () {
    for ($i = 0; $i < 5; $i++) {
        go(function () {
            $mysqli = new mysqli("mysql", "username", "password", "test");
            $stmt = $mysqli->prepare("SELECT SLEEP(3)");
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
        });
    }
});
