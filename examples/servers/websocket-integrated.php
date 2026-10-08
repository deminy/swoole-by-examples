#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a WebSocket server to demonstrate some advanced usages, where we have:
 *     * A WebSocket server to handle WebSocket requests.
 *     * A cron job set up to run every second, in a dedicated process.
 *     * A dedicated process to process task queues asynchronously.
 *
 * Both dedicated processes are user processes, added to the server through method $server->addProcess(). The server
 * starts them along with its worker processes, and stops them when it shuts down. A user process is expected to run
 * forever: if its callback returns, the server starts the process again.
 *
 * To show that, the script starts the server, sends a WebSocket message to it and prints the reply, then lets the
 * server run for 2.5 seconds, while the cron job and the task queue process print their messages. Then the script
 * shuts the server down. In a real application, the cron job and the tasks would run at longer intervals (e.g., every
 * few minutes).
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/websocket-integrated.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Process;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0, SWOOLE_BASE);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);
$server->on(
    'message',
    function (Server $server, Frame $frame): void {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);

// Once the server has started, send a WebSocket message to it (the "workerStart" callback runs in a coroutine), then
// let the server run for a while before shutting it down.
$server->on(
    'workerStart',
    function (Server $server, int $workerId): void {
        $client = new Client('127.0.0.1', $server->port);
        $client->upgrade('/');
        $client->push('Swoole');
        $frame = $client->recv();
        echo 'Reply from the WebSocket server: ', ($frame instanceof Frame) ? $frame->data : '(none)', PHP_EOL;
        $client->close();

        Coroutine::sleep(2.5);
        $server->shutdown();
    }
);

$process = new Process(
    function (): void {
        // A user process is expected to run forever: if its callback returns, the server starts the process again.
        while (true) { // @phpstan-ignore while.alwaysTrue
            // To simulate task processing. Here we simply print out a message.
            // In reality, a task queue system works like following:
            //   1. Use some storage system (e.g., Redis) to store tasks dispatched from worker processes, cron jobs or
            //      another source;
            //   2. In the task processing processes, get tasks from the storage system, process them, then remove them
            //      once done.
            echo 'Task processed.', PHP_EOL;
            sleep(1);
        }
    }
);
$server->addProcess($process);

$process = new Process(
    function (): void {
        while (true) { // @phpstan-ignore while.alwaysTrue
            sleep(1);
            echo 'Cron job executed.', PHP_EOL; // To simulate cron job executions.
        }
    }
);
$server->addProcess($process);

$server->start();
