#!/usr/bin/env php
<?php
/**
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=app") bash -c "time ./csp/waitgroup.php"
 *
 * This script takes about 3 seconds to finish, with 3 coroutines created.
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
    $wg->wait();
});
