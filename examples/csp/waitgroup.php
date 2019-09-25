#!/usr/bin/env php
<?php
/**
 * This example shows how to wait for a collection of coroutines to finish in PHP applications. It has 3 coroutines
 * executed, and takes about 3 seconds to finish.
 *
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=app") bash -c "time ./csp/waitgroup.php"
 *
 * Class \Swoole\Coroutine\WaitGroup is defined in this file:
 *     https://github.com/swoole/swoole-src/blob/master/library/core/Coroutine/WaitGroup.php
 */

use Swoole\Coroutine\WaitGroup;

$wg = new WaitGroup();
go(function () use ($wg) {
    go(function () use ($wg) {
        $wg->add();
        co::sleep(1);
        $wg->done();
    });
    go(function () use ($wg) {
        $wg->add();
        co::sleep(2);
        $wg->done();
    });
    go(function () use ($wg) {
        $wg->add();
        co::sleep(3);
        $wg->done();
    });
    $wg->wait(); // Wait those 3 coroutines to finish.

    // Any code here won't be executed until all 3 coroutines created in this function finish execution.
});
