#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use channels to communicate among different coroutines.
 *
 * Key notes about channels:
 *     * The standard way to communicate among coroutines is to use channels.
 *     * A Swoole channel can store any type of data, while in Go a channel is for a specific type of data.
 *     * When a channel is full and you try to push new data into it, this push operation is paused until the channel is
 *       not full (or timeout happens).
 *     * When a channel is empty and you try to pop out data from it, this pop operation is paused until data is
 *       available in the channel (or timeout happens).
 *
 * In this example, a channel with a capacity of 2 is created. Two coroutines each send an HTTP request and push the
 * HTTP status code into the channel, while a third coroutine pops both of them out and prints them. This example
 * needs Internet access. The output looks like the following (php.net answers plain HTTP requests with a 301
 * redirect):
 *     array(2) {
 *       [0]=>
 *       string(3) "301"
 *       [1]=>
 *       int(404)
 *     }
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/channel.php"
 */

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $channel = new Channel(2);

    go(function () use ($channel): void {
        $result = [];
        for ($i = 0; $i < 2; $i++) {
            $result[] = $channel->pop();
        }
        var_dump($result);
    });

    go(function () use ($channel): void {
        $cli = new Client('php.net');
        $cli->get('/');
        // A channel can hold any type of data: a string is pushed here and an integer below.
        $channel->push("{$cli->statusCode}");
    });

    go(function () use ($channel): void {
        $cli = new Client('swoole.com');
        $cli->get('/');
        $channel->push((int) $cli->statusCode);
    });

    // At this point, all three coroutines are paused.
});
