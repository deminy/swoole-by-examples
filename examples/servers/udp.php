#!/usr/bin/env php
<?php

/**
 * In this example we start a UDP server on port 9506. Please check next section to see how to test the example.
 *
 * To test the UDP server, you can execute a netcat command in the client container to talk to the UDP server:
 *     docker exec -ti $(docker ps -qf "name=client") nc -u server 9506
 *
 * Now you can start typing something and hit the return button to send it to the UDP server. Whatever you type, the UDP
 * server echos it back.
 *
 * Once done, you can press CTRL+C to stop.
 */

use Swoole\Server;

$server = new Server("0.0.0.0", 9506, SWOOLE_BASE, SWOOLE_SOCK_UDP);
$server->on(
    "packet",
    function (Server $server, string $data, array $clientInfo) {
        $server->sendto($clientInfo["address"], $clientInfo["port"], $data);
    }
);
$server->start();
