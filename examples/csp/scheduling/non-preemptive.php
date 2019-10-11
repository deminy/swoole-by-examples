#!/usr/bin/env php
<?php
/**
 * In this example, the first coroutine keeps running all the time, while the second coroutine has no chance of getting
 * executed. The script will keep printing out integers.
 *
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=server") bash -c "./csp/scheduling/non-preemptive.php"
 */

co\run(
    function () {
        go(function () {
            $i = 0;
            while (true) {
                echo $i++, "\n";
            }
        });

        go(function () {
            throw new Exception("Quitting.");
        });
    }
);
