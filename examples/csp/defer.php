#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example is to show how defer works in Swoole. It takes about 1 second to finish, and prints out "1234567".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/defer.php"
 */
go(function () {
    echo '1';
    defer(function () {
        echo "7\n";
    });

    echo '2';
    defer(function () {
        echo '6';
    });

    echo '3';
    co::sleep(1);
    echo '5';
});
echo '4';

Swoole\Event::wait();
