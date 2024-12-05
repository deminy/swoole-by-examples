#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example two TCP requests are made in non-blocking mode to two different TCP servers. In most cases it should
 * receive a response from the 2nd TCP server first (since the 2nd one should be faster).
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/tcp.php"
 *
 * Here are the source code of the two TCP servers created:
 * 1. event-driven style (port 9505): https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp1.php
 * 2. coroutine style (port 9507):    https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp2.php
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
