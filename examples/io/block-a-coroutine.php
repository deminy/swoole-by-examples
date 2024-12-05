#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we will show how to block a coroutine in different ways. Please note that it only blocks current
 * coroutine but not the whole process.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./io/block-a-coroutine.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./io/block-a-coroutine.php"
 *
 * This script takes about 4 seconds to finish.
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

use function Swoole\Coroutine\run;

// When function Swoole\Coroutine\run() is called, it automatically creates a main coroutine to run the code inside.
run(function (): void {
    // The easiest way to block a coroutine is to use method \Swoole\Coroutine::sleep(). e.g.,
    Coroutine::sleep(0.001); // To block current coroutine for 1 millisecond.

    // Here we show another approach (to block a coroutine) by using a channel.
    $channel = new Channel(); // A channel of size 1 is created.
    for ($i = 0; $i < 2; $i++) {
        echo date('H:i:s'), " (round {$i})", PHP_EOL;

        // Method \Swoole\Coroutine\Channel::pop() is a blocking method, which means it will block the current coroutine
        // until the channel is not empty or the timeout is reached.
        //
        // In our case, the pop() method call will block the current coroutine for 2 seconds, and then the coroutine will
        // be resumed. The method call will return false back and an error code is set on property $channel->errCode.
        //
        // Please note that the pop() method blocks the current coroutine only, not the whole process.
        $channel->pop(2.0);

        echo date('H:i:s'), " (round {$i})", PHP_EOL;
    }
});
