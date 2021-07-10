#!/usr/bin/env php
<?php

/**
 * The example is to show how to yield and resume coroutines. It takes about 1 second to finish, and prints out
 * "1234567".
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./csp/coroutines/yield-and-resume.php"
 */

$cid = go(function () {
    echo "1";
    co::yield();
    echo "6";
});

echo "2";

go(function () use ($cid) {
    echo "3";
    co::sleep(1);
    echo "5";
    co::resume($cid);
    echo "7\n";
});

echo "4";
