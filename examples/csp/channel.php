#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use channels to communicate among different coroutines.
 *
 * Key notes about channels:
 *     * The standard way to communicate among coroutines is to use channels.
 *     * A PHP channel can store any type of data, while in Golang a channel is for a specific type of data.
 *     * When a channel is full and you try to push new data into it, this push operation is paused until the channel is
 *       not full (or timeout happens).
 *     * When a channel is empty and you try to pop out data from it, this pop operation is paused until there are data
 *       available in the channel (or timeout happens).
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
        $channel->push("{$cli->statusCode}");
    });

    go(function () use ($channel): void {
        $cli = new Client('swoole.com');
        $cli->get('/');
        $channel->push((int) $cli->statusCode);
    });

    // At this point, all three coroutines are paused.
});
