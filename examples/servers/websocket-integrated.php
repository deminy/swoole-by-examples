#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a WebSocket server to demonstrate some advanced usages, where we have:
 *     * A WebSocket server to handle WebSocket requests.
 *     * A cronjob setup to run every 31 seconds.
 *     * A dedicated process to process task queues asynchronously.
 *
 * To test the WebSocket server, you can run following command to get a hello message back from the WebSocket server:
 *     docker compose exec -ti client bash -c "echo Swoole | websocat ws://server:9508"
 * To check logs created by the cron job and the asynchronous task queue process, you can run following command in the
 * console:
 *     docker-compose logs -f
 */

use Swoole\Process;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

$server = new Server('0.0.0.0', 9508, SWOOLE_BASE);
$server->on(
    'message',
    function (Server $server, Frame $frame): void {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);

$process = new Process(
    function (): void {
        // To simulate task processing. Here we simply print out a message.
        // In reality, a task queue system works like following:
        //   1. Use some storage system (e.g., Redis) to store tasks dispatched from worker processes, cron jobs or
        //      another source;
        //   2. In the task processing processes, get tasks from the storage system, process them, then remove them once
        //      done.
        echo 'Task processed (in file ',  __FILE__, ').', PHP_EOL;
        sleep(29);
    }
);
$server->addProcess($process);

$process = new Process(
    function (): void {
        while (true) { // @phpstan-ignore while.alwaysTrue
            sleep(31);
            echo 'Cron executed (in file ',  __FILE__, ').', PHP_EOL; // To simulate cron executions.
        }
    }
);
$server->addProcess($process);

$server->start();
