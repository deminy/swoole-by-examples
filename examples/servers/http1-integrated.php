#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1 server to demonstrate some advanced usages, where we have:
 *     * Multiple worker processes and task worker processes started to handle HTTP requests and sync/async tasks.
 *     * Two cron jobs. One runs every 61 seconds, and the other runs every 63 seconds.
 *     * HTTP endpoints to dispatch sync/async tasks.
 *
 * Task workers are separate processes that run slow jobs handed over by worker processes through method
 * $server->task() and friends, so the worker processes stay free to answer requests. The first cron job is a timer
 * created by method \Swoole\Timer::tick() in the "onStart" callback (which runs once, when the server starts); the
 * second one is a loop that sleeps between runs, in a coroutine created in worker process #0.
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. The output of the cron jobs and the tasks can
 * be viewed with:
 *     docker compose logs -f server
 *
 * You can run the following curl commands to see different outputs:
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
        // A single string per echo statement: several processes of this server print to the same output concurrently,
        // and an echo statement with multiple arguments (one write per argument) could interleave with their output.
        echo '[HTTP1-ADVANCED] # of CPU units: ' . swoole_cpu_num() . PHP_EOL;

        // Here we start the first cron job that runs every 61 seconds.
        Timer::tick(
            1000 * 61,
            function (): void {
                echo '[HTTP1-ADVANCED] This message is printed out every 61 seconds. (' . date('H:i:s') . ')' . PHP_EOL;
            }
        );
    }
);
$server->on(
    'workerStart',
    function (Server $server, int $workerId): void {
        echo "[HTTP1-ADVANCED] Worker #{$workerId} is started." . PHP_EOL;
        if ($workerId === 0) {
            // Here we start the second cron job that runs every 63 seconds.
            Coroutine::create(function (): void {
                while (true) { // @phpstan-ignore while.alwaysTrue
                    Coroutine::sleep(63);
                    echo '[HTTP1-ADVANCED] This message is printed out every 63 seconds. (' . date('H:i:s') . ')'
                        . PHP_EOL;
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
                // To dispatch an asynchronous task.
                $server->task((object) ['type' => 'task']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskwait':
                // To dispatch a task and wait for its result (the request waits until the task finishes, or until the
                // timeout, 0.5 seconds by default).
                $result = $server->taskwait(['type' => 'taskwait']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskWaitMulti':
                // To dispatch multiple tasks and wait for all of them. NOTE: this blocks the whole worker process;
                // inside coroutines, use taskCo() instead.
                $server->taskWaitMulti(['taskWaitMulti #0', 'taskWaitMulti #1', 'taskWaitMulti #2']);
                $response->end($type . PHP_EOL);
                break;
            case 'taskCo':
                // To dispatch multiple tasks, and wait until they all finish.
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
                // To dispatch an asynchronous task, and process the response through a callback function.
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
        echo 'Task received with incoming data (serialized for display): ' . serialize($data) . PHP_EOL;

        return $data;
    }
);
$server->on(
    'finish',
    function (Server $server, int $taskId, $data) {
        echo 'Task returned with data (serialized for display): ' . serialize($data) . PHP_EOL;

        return $data;
    }
);

$server->start();
