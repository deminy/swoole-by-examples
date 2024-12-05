#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we start a web server with a cronjob setup to run every 19 seconds. The cron job sends a message "[INTERRUPTIBLE-SLEEP] Simulating cronjob execution.(case 1)" to the logs of the server container when executed.
 *
 * The problem with traditional cronjobs is that they don't get a chance to execute at a last time before the server
 * shuts down. For example, if the cronjob is scheduled to run every 19 seconds, and the server is shutting down 15
 * seconds after the last cronjob execution, then the cronjob will never get a chance to execute one more time before
 * the server shuts down.
 *
 * In this example, we use Channel to schedule a cronjob to run every 19 seconds. The implementation allows the cronjob
 * to execute one more time by checking if the Channel is closed when server shuts down.
 *
 * When running the following command to stop and restart the server, you will see the cronjob is executed one more time
 * with message "[INTERRUPTIBLE-SLEEP] Simulating cronjob execution.(case 2)" logged in the logs of the server container:
 *     docker compose exec -t server bash -c "supervisorctl restart interruptible-sleep"
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$exited = new Channel();
$server = new Server('0.0.0.0', 9512);

$server->on('workerStart', function (Server $server, int $workerId) use ($exited): void {
    if ($workerId === 0) {
        Coroutine::create(function () use ($exited): void {
            // Here we start the second cron job that makes an HTTP request every 19 seconds.
            while (true) {
                echo '[INTERRUPTIBLE-SLEEP] Simulating cronjob execution. (case 1)', PHP_EOL;
                $exited->pop(19);
                if ($exited->errCode === SWOOLE_CHANNEL_CLOSED) {
                    echo '[INTERRUPTIBLE-SLEEP] Simulating cronjob execution. (case 2)', PHP_EOL;
                    break;
                }
            }
            echo '[INTERRUPTIBLE-SLEEP] The cronjob has exited.', PHP_EOL;
        });
    }
});
$server->on('workerExit', function (Server $server, int $workerId) use ($exited): void {
    echo "[INTERRUPTIBLE-SLEEP] Worker #{$workerId} is exiting.", PHP_EOL;
    if ($workerId === 0) {
        Coroutine::create(function () use ($exited): void {
            $exited->close();
        });
    }
    echo "[INTERRUPTIBLE-SLEEP] Worker #{$workerId} has exited.", PHP_EOL;
});
$server->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});

$server->start();
