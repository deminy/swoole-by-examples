#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to establish a UDP connection.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./clients/udp.php"
 *
 * Here is the source code of the UDP server:
 * @see https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/udp.php
 */

use Swoole\Client;

go(function () {
    $client = new Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
    $client->connect('server', 9506);
    $client->send('Hello Swoole!');
    echo $client->recv() . "\n";
    $client->close();
});
