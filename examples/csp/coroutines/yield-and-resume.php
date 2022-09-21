#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example is to show how to yield and resume coroutines. It takes about 1 second to finish, and prints out
 * "12345678".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/yield-and-resume.php"
 */
Co\run(function () {
    $cid = go(function () {
        echo '1';
        co::yield();
        echo '6';
    });

    echo '2';

    go(function () use ($cid) {
        echo '3';
        co::sleep(1);
        echo '5';
        co::resume($cid);
        echo '7';
    });

    echo '4';
});
echo "8\n";
