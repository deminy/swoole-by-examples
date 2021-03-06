#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The example compares the return statements used in blocking function calls and non-blocking function calls.
 *
 * The output "123456" is printed out in following order:
 *     * The digit "1" is printed out first.
 *     * After one second, next four digits "2345" are printed out.
 *     * After another second, the last digit "6" is printed out.
 *
 * Notes:
 *     * Function blocking() executes in blocking mode, just like what we used to see.
 *     * Function nonBlocking() executes in non-blocking mode. It returns an integer value "5" back first before
 *       finishing executing the nested coroutine inside it.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./io/blocking-vs-non-blocking.php"
 */
function blocking()
{
    go(function () {
        echo '1';
        sleep(2);
        echo '2';
    });
    return '3';
}

function nonBlocking()
{
    go(function () {
        echo '4';
        co::sleep(2);
        echo '6', "\n";
    });
    return '5';
}

echo blocking(), nonBlocking();
