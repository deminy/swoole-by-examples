<?php
/**
 * This script is similar to "channel.php" but has debug messages printed out showing # of active coroutines.
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;

$chan = new chan(2);

go(function () use ($chan) {
    $result = [];
    for ($i = 0; $i < 2; $i++) {
        $result[] = $chan->pop();
    }
});

go(function () use ($chan) {
    echo count(Coroutine::listCoroutines()), " active coroutines when entering the 2nd coroutine.\n";

    $cli = new Client('swoole.com');
    $ret = $cli->get('/');

    echo count(Coroutine::listCoroutines()), " active coroutines once done fetching web page in the 2nd coroutine.\n";

    $chan->push("{$cli->statusCode}");
});

go(function () use ($chan) {
    echo count(Coroutine::listCoroutines()), " active coroutines when entering the 3rd coroutine.\n";

    $cli = new Client('php.net');
    $ret = $cli->get('/');

    echo count(Coroutine::listCoroutines()), " active coroutines once done fetching web page in the 3rd coroutine.\n";

    $chan->push((int) $cli->statusCode);
});

echo count(Coroutine::listCoroutines()), " active coroutines when reaching the end of the whole PHP script.\n";
