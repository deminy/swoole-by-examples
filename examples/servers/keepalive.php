#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how to detect dead TCP connections using TCP keepalive. This script is very similar to the
 * TCP server one (tcp.php), except that here we use a different port #, have TCP keepalive enabled, and adjust some
 * parameters related to TCP keepalive.
 *
 * How to check if the script works? In real life, you can make a test connection to the server then unplug the network
 * cable on the server side (or on the client side), but in our Docker environment we are unable to do that. However,
 * you can still check network connection status to see if it works:
 * 1. In the client container, make a TCP connection to the server:
 *        docker compose exec -ti client bash -c "nc server 9602"
 * 2. In the server container, monitor network connection status:
 *        docker compose exec -t server bash -c "watch -n 1 'netstat -a -n -o | grep :9602'"
 *    From the output you will see an "ESTABLISHED keepalive" line, which is the TCP connection from the client
 *    container. The first number in brackets is the number of seconds left before a keepalive probe will be sent. You
 *    will see this number getting smaller and smaller.
 */

use Swoole\Constant;
use Swoole\Server;

$server = new Server('0.0.0.0', 9602);
$server->set(
    [
        Constant::OPTION_OPEN_TCP_KEEPALIVE => 1,

        // Following 3 parameters are to adjust timeout. Run this command to check default values in the container:
        //     docker compose exec -t server bash -c "tail -n +1 /proc/sys/net/ipv4/tcp_keepalive_*"
        Constant::OPTION_TCP_KEEPIDLE       => 7200,
        Constant::OPTION_TCP_KEEPCOUNT      => 9,
        Constant::OPTION_TCP_KEEPINTERVAL   => 75,
    ]
);
$server->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, $data);
    }
);
$server->start();
