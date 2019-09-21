#!/usr/bin/env php
<?php
/**
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=app") bash -c "./defer.php"
 *
 * The script takes about 1 second to finish, and prints out "123456".
 */

go(function () {
    echo "1";
    defer(function () {
        echo "6";
    });

    echo "2";
    defer(function () {
        echo "5";
    });

    echo "3";
    co::sleep(1);
    echo "4";
});
