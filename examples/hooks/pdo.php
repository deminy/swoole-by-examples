#!/usr/bin/env php
<?php

/**
 * In this example, we make five queries in separate MySQL connections; each query takes three seconds to finish.
 * In non-blocking mode, it takes 15 seconds to make the five queries; however, since they are executed asynchronously
 * in Swoole, it takes barely over three seconds to finish all the queries.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./hooks/pdo.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "time ./hooks/pdo.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_TCP]);

co\run(function () {
    for ($i = 0; $i < 5; $i++) {
        go(function () {
            $pdo = new PDO('mysql:host=mysql;dbname=test', 'username', 'password');
            $stmt = $pdo->prepare("SELECT SLEEP(3)");
            $stmt->execute();
            $stmt = null;
            $pdo = null;
        });
    }
});
