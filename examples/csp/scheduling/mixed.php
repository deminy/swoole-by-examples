#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we set PHP option "swoole.enable_preemptive_scheduler" to 1 at line 14, allowing different
 * coroutines to share the CPU. However, when the first coroutine is started, it immediately disables the scheduler
 * defined in Swoole, starts printing out 100,000 integers, then enables the scheduler; the second coroutine could be
 * executed only after the scheduler is enabled at line 24.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./csp/scheduling/mixed.php"
 */
ini_set('swoole.enable_preemptive_scheduler', 1);

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
            throw new Exception('Quitting.');
        });
    }
);
