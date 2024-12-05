#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1 server to demonstrate some advanced usages, where we have:
 *     * multiple worker processes started to handle HTTP requests and sync/async tasks.
 *     * Two cron jobs. One runs every 61 seconds, and the other runs every 63 seconds.
 *     * HTTP endpoints to deploy sync/async tasks.
 *
 * You can run following curl commands to see different outputs:
 *   docker compose exec -t client bash -c "curl -i http://server:9502"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=task"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=taskwait"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=taskWaitMulti"
 *   docker compose exec -t client bash -c "curl -i http://server:9502?type=taskCo"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

$server = new Server('0.0.0.0', 9502);
$server->set(
    [
        Constant::OPTION_WORKER_NUM      => 2,
        Constant::OPTION_TASK_WORKER_NUM => swoole_cpu_num(),
    ]
);

$server->on(
    'start',
    function (Server $server): void {
        echo '[HTTP1-ADVANCED]: # of CPU units: ', swoole_cpu_num(), PHP_EOL;

        // Here we start the first cron job that runs every 61 seconds.
        Timer::tick(
            1000 * 61,
            function (): void {
                echo '[HTTP1-ADVANCED]: This message is printed out every 61 seconds. (', date('H:i:s'), ')', PHP_EOL;
            }
        );
    }
);
$server->on(
    'workerStart',
    function (Server $server, int $workerId): void {
        echo "[HTTP1-ADVANCED] Worker #{$workerId} is started.", PHP_EOL;
        if ($workerId === 0) {
            // Here we start the second cron job that runs every 63 seconds.
            Coroutine::create(function (): void {
                while (true) { // @phpstan-ignore while.alwaysTrue
                    Coroutine::sleep(63);
                    echo '[HTTP1-ADVANCED]: This message is printed out every 63 seconds. (', date('H:i:s'), ')', PHP_EOL;
                }
            });
        }
    }
);
$server->on(
    'request',
    function (Request $request, Response $response) use ($server): void {
        $type = $request->get['type'] ?? '';
        switch ($type) {
            case 'task':
                // To deploy an asynchronous task.
                $server->task((object) ['type' => 'task']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskwait':
                // To deploy an asynchronous task, and wait until it finishes.
                $result = $server->taskwait(['type' => 'taskwait']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskWaitMulti':
                // To deploy multiple asynchronous tasks, and wait until they finish. (legacy implementation)
                $server->taskWaitMulti(['taskWaitMulti #0', 'taskWaitMulti #1', 'taskWaitMulti #2']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskCo':
                // To deploy multiple asynchronous tasks, and wait until they finish.
                $result = $server->taskCo(
                    [
                        'taskCo #0',
                        ['type' => 'taskCo #1'],
                        (object) ['type' => 'taskCo #2'],
                    ]
                );
                $response->end(print_r($result, true));
                break;
            default:
                // To deploy an asynchronous task, and process the response through a callback function.
                $server->task('taskCallback', -1, function (Server $server, int $taskId, $data) use ($response): void {
                    $response->end($data . PHP_EOL);
                });
                break;
        }
    }
);
$server->on(
    'task',
    function (Server $server, int $taskId, int $reactorId, $data) {
        echo 'Task received with incoming data (serialized already): ', serialize($data), PHP_EOL;

        return $data;
    }
);
$server->on(
    'finish',
    function (Server $server, int $taskId, $data) {
        echo 'Task returned with data (serialized already): ', serialize($data), PHP_EOL;

        return $data;
    }
);

$server->start();
