#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how different server events are triggered.
 *
 * NOTES:
 * 1. Event "onStart", "onManagerStart", and "onWorkerStart" are triggered in different processes, and they don't always
 *    run in certain order.
 * 2. When the server is reloaded (through method call "$server->reload()"), there are four types of events triggered:
 *    a). Event "onBeforeReload" and "onAfterReload" are triggered in the manager process.
 *    b). Event "onWorkerExit" and "onWorkerStart" are triggered in each worker process and task worker process.
 *    c). Event "onAfterReload" doesn't necessarily always happen before/after the "onWorkerStart" events, since they
 *        run in different processes.
 * 3. About "onWorker*" events:
 *    a) Event "onWorkerStart" happens both in worker processes and in task worker processes.
 *    a) Event "onWorkerStop" happens in worker processes only. It won't happen in task worker processes.
 *    a) Event "onWorkerExit" happens only when option \Swoole\Constant::OPTION_RELOAD_ASYNC is turned on.
 *    a) Event "onWorkerError" happens in the manager process.
 * 4. Event "onReceive" is triggered in TCP servers, while event "onPacket" is triggered in UDP servers only. They both
 *    happen after event "onConnect" but before event "onClose".
 * 5. Event "onRequest" is only to process HTTP/1, HTTP/2, and WebSocket requests.
 * 6. Event "onTask" is triggered in task worker processes only, while event "onFinish" is triggered in worker processes
 *    only. Event "onFinish" is triggered only when both conditions are met:
 *    a) The task is triggered by method call "$server->task()".
 *    b) Inside the callback function of the "onTask" event, either method call "$task->finish()" is executed, or a
 *       return statement is used to return something back.
 * 7. There are some events not triggered in this example:
 *    a) Event "onPacket". It is triggered in UDP servers only.
 *    b) Event "onHandshake", "onOpen", "onMessage", and "onDisconnect". They are to process WebSocket requests.
 *
 * How to run this script:
 *   docker run --rm -v $(pwd):/var/www -ti phpswoole/swoole php ./examples/servers/server-events.php
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

function printMessage(string $message, bool $newLine = false): void
{
    echo ($newLine ? PHP_EOL : ''), 'INFO (', date('H:i:s'), "): {$message}", PHP_EOL;
}

$server = new Server('0.0.0.0', 9509);
$server->set(
    [
        Constant::OPTION_WORKER_NUM      => 1, // One worker process to process HTTP requests. Its worker ID is "0".
        Constant::OPTION_TASK_WORKER_NUM => 1, // One task worker process to process tasks. Its worker ID is "1".
    ]
);

$server->on('start', function (Server $server): void {
    printMessage('Event "onStart" is triggered.');
});

$server->on('managerStart', function (Server $server): void {
    printMessage('Event "onManagerStart" is triggered.');

    // To make an HTTP request to the sever itself 50 milliseconds later.
    Timer::after(50, function (): void {
        printMessage('Make an HTTP request to the server.' . PHP_EOL, true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:9509');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    });

    // To reload the HTTP server 100 milliseconds later.
    Timer::after(100, function () use ($server): void {
        printMessage('Reload the server.' . PHP_EOL, true);
        $server->reload();
    });

    // To shutdown the HTTP server 150 milliseconds later.
    Timer::after(150, function () use ($server): void {
        printMessage('Shutdown the server.' . PHP_EOL, true);
        $server->shutdown();
    });
});
$server->on('managerStop', function (Server $server): void {
    printMessage('Event "onManagerStop" is triggered.');
});

$server->on('workerStart', function (Server $server, int $workerId): void {
    printMessage("Event \"onWorkerStart\" is triggered in worker #{$workerId}.");
});
$server->on('workerStop', function (Server $server, int $workerId): void {
    printMessage("Event \"onWorkerStop\" is triggered in worker #{$workerId}.");
});
$server->on('workerError', function (Server $server, int $workerId, int $exitCode, int $signal): void {
    printMessage("Event \"onWorkerError\" is triggered in worker #{$workerId}.");
});
$server->on('workerExit', function (Server $server, int $workerId): void {
    printMessage("Event \"onWorkerExit\" is triggered in worker #{$workerId}.");
});

$server->on('connect', function (Server $server, int $fd, int $reactorId): void {
    printMessage('Event "onConnect" is triggered.');
});
$server->on('receive', function (Server $server, int $fd, int $reactorId, string $data): void {
    printMessage('Event "onReceive" is triggered.');
});
$server->on('close', function (Server $server, int $fd, int $reactorId): void {
    printMessage('Event "onClose" is triggered.' . PHP_EOL);
});

$server->on('request', function (Request $request, Response $response) use ($server): void {
    printMessage('Event "onRequest" is triggered.');
    $response->end('OK' . PHP_EOL);
    Coroutine::create(function () use ($server): void {
        Coroutine::sleep(0.01);
        $server->task('Hello, World!');

        Coroutine::sleep(0.01);
        $server->sendMessage('Hello, World!', 1);
    });
});

$server->on('task', function (Server $server, int $taskId, int $srcWorkerId, $data): void {
    printMessage('Event "onTask" is triggered.');

    // This is to trigger the "onFinish" event.
    // Please note that "onTask" events are processed in task worker processes, while "onFinish" events are processed
    // in event worker processes (where method call $server->task*() was made to trigger the "onTask" events).
    $server->finish('Hello World!');
});
$server->on('finish', function (Server $server, int $taskId, $data): void {
    printMessage('Event "onFinish" is triggered.');
});
$server->on('pipeMessage', function (Server $server, int $srcWorkerId, $message) {
    printMessage('Event "onPipeMessage" is triggered.');
    return $message;
});

$server->on('beforeReload', function (Server $server): void {
    printMessage('Event "onBeforeReload" is triggered.');
});
$server->on('afterReload', function (Server $server): void {
    printMessage('Event "onAfterReload" is triggered.');
});

$server->on('shutdown', function (Server $server): void {
    printMessage('Event "onShutdown" is triggered.' . PHP_EOL);
});

printMessage('Start the server.' . PHP_EOL, true);
$server->start();
