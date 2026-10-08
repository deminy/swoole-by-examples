#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to send a UDP datagram to a UDP server and read the reply, using the coroutine client
 * \Swoole\Coroutine\Client.
 *
 * UDP is connectionless: calling method connect() on a UDP client doesn't establish a connection, it only sets the
 * default address that datagrams are sent to (and received from).
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/udp.php"
 *
 * Here is the source code of the UDP server:
 *     https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/udp.php
 */

use Swoole\Coroutine\Client;

use function Swoole\Coroutine\run;

run(function (): void {
    $client = new Client(SWOOLE_SOCK_UDP);
    $client->connect('server', 9506);
    $client->send('Hello Swoole!');
    echo $client->recv(), PHP_EOL; // @phpstan-ignore echo.nonString
    $client->close();
});
