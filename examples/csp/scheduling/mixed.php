#!/usr/bin/env php
<?php
/**
 * In this example, we set PHP option "swoole.enable_preemptive_scheduler" to 1 at line 13, allowing different
 * coroutines to share the CPU. However, when the first coroutine is started, it immediately disables the schedule
 * defined in Swoole, starts printing out 100,000 integers, then enables the schedule; the second coroutine could be
 * executed only after the schedule is enabled at line 23.
 *
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=client") bash -c "./csp/scheduling/mixed.php"
 */

ini_set("swoole.enable_preemptive_scheduler", 1);

co\run(
    function () {
        go(function () {
            $i = 0;
            Swoole\Coroutine::disableScheduler();
            while ($i < 100000) {
                echo $i++, "\n";
            }
            Swoole\Coroutine::enableScheduler();
        });

        go(function () {
            throw new Exception("Quitting.");
        });
    }
);
