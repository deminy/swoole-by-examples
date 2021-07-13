#!/usr/bin/env php
<?php

/**
 * In this example we start a TCP server in coroutine style on port 9507.
 *
 * To test the TCP server, you can execute a netcat command in the client container to talk to the TCP server:
 *     docker exec -ti $(docker ps -qf "name=client") nc server 9507
 *
 * Now you can start typing something and hit the return button to send it to the TCP server. Whatever you type, the TCP
 * server echos it back.
 *
 * Once done, you can press CTRL+C to stop.
 */

use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;

co\run(function () {
    $server = new Server("0.0.0.0", 9507);
    $server->handle(function (Connection $conn) {
        while (true) {
            if ($data = $conn->recv()) {
                $conn->send($data);
            } else {
                $conn->close();
                break;
            }
        }
    });
    $server->start();
});
