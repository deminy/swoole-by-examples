#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example two TCP requests are sent concurrently (in two coroutines) to two different TCP servers, both of
 * which echo the message back. Since both servers respond almost instantly, the order of the two responses may vary
 * from run to run.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/tcp.php"
 *
 * Here is the source code of the two TCP servers:
 * 1. event-driven style (port 9505):
 *    https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp-event-driven.php
 * 2. coroutine style (port 9507):
 *    https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp-coroutine-style.php
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Client;

use function Swoole\Coroutine\run;

run(function (): void {
    foreach ([9505, 9507] as $port) {
        Coroutine::create(function () use ($port): void {
            $client = new Client(SWOOLE_TCP);
            $client->connect('server', $port);
            $client->send("client side message to port {$port}");
            echo $client->recv(), PHP_EOL; // @phpstan-ignore echo.nonString
            $client->close();
        });
    }
});
