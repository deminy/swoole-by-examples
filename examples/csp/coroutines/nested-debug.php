#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This script is to help to understand how nested coroutines are executed in order; it works similarly to script
 * "nested.php" under the same directory. Once executed, it prints out "1234567890".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/nested-debug.php"
 */
Co\run(function () {
    go(function () {
        echo '1';
        go(function () {
            echo '2';
            Co::sleep(3);
            echo '6';
            go(function () {
                echo '7';
                Co::sleep(2);
                echo '9';
            });
            echo '8';
        });
        echo '3';
        Co::sleep(1);
        echo '5';
    });
    echo '4';
});
echo "0\n";
