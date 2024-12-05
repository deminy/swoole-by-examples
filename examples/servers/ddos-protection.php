#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1 server with DDoS protection enabled.
 *
 * DDoS protection in Swoole-based application server can be implemented by:
 *     1. setting option \Swoole\Constant::OPTION_ENABLE_DELAY_RECEIVE to true.
 *     2. using method \Swoole\Server::confirm() in the callback function of event "onConnect".
 *
 * For the HTTP server created in this example, 1/3 of the HTTP requests take about 2 seconds to finish; others
 * get HTTP responses back immediately.
 *
 * You can run following curl command to make HTTP/1 requests to the server:
 *   docker compose exec -t client bash -c "curl -i http://server:9510"
 *
 * To see how much time it takes before an HTTP response is returned from the server:
 *   docker compose exec -t client bash -c "time curl -s http://server:9510"
 *
 * To run a benchmark by making 50 HTTP requests concurrently:
 *   docker compose exec -t client bash -c "ab -n 50 -c 50 http://server:9510/"
 */

use Swoole\Constant;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

$server = new Server('0.0.0.0', 9510);

$server->set(
    [
        // For testing purpose, there is only one worker process to handle HTTP requests.
        Constant::OPTION_WORKER_NUM           => 1,
        Constant::OPTION_ENABLE_DELAY_RECEIVE => true,
    ]
);

// In this example, 1/3 of the traffic is processed at a later time, but not in real time. In reality,
// there are different ways to enable DDoS protection, like rate limiting, blocking IP address, etc.
$server->on('connect', function (Server $server, int $fd, int $reactorId): void {
    if (($fd % 3) === 0) {
        // 1/3 of all HTTP requests have to wait for two seconds before being processed.
        Timer::after(2000, function () use ($server, $fd): void {
            $server->confirm($fd);
        });
    } else {
        // 2/3 of all HTTP requests are processed immediately by the server.
        $server->confirm($fd);
    }
});
$server->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});

$server->start();
