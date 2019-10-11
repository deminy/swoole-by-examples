#!/usr/bin/env php
<?php
/**
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=server") bash -c "./timer/timer.php"
 *
 * Check the output and see how Timer works in Swoole.
 *
 * The example can be implemented using coroutines only (without the \Swoole\Timer class). Please check script
 * "coroutine-style.php" for details.
 */

use Swoole\Timer;

$id = Timer::tick(100, function () {
    echo "Function call is triggered every 100 milliseconds by the timer.\n";
});
Timer::after(500, function () use ($id) {
    Timer::clear($id);
    echo "The timer is cleared at the 500th millisecond.\n";
});
Timer::after(1000, function () use ($id) {
    if (!Timer::exists($id)) {
        echo "The timer should not exist at the 1,000th millisecond.\n";
    }
});
