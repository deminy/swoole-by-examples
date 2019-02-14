<?php

use Swoole\Coroutine\Http\Client;

$chan = new chan(2);

go(function () use ($chan) {
    $result = [];
    for ($i = 0; $i < 2; $i++) {
        $result[] = $chan->pop();
    }
    var_dump($result);
});

go(function () use ($chan) {
    $cli = new Client('sunshinephp.com');
    $ret = $cli->get('/');
    $chan->push((int) $cli->statusCode);
});

go(function () use ($chan) {
    $cli = new Client('php.net');
    $ret = $cli->get('/');
    $chan->push("{$cli->statusCode}");
});

/**
 * @see https://segmentfault.com/a/1190000017243966 PHP 协程：Go + Chan + Defer
 */
