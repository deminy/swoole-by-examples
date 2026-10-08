#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how different server events are triggered.
 *
 * A \Swoole\Server runs in several processes: the master process (event "onStart"), the manager process (event
 * "onManagerStart"), which starts and restarts the other processes, the worker processes (also called event workers),
 * which handle the network events such as HTTP requests, and the task worker processes, which run slow jobs handed
 * over by the worker processes through method $server->task(). This server has one worker process (#0) and one task
 * worker process (#1).
 *
 * NOTES:
 * 1. Events "onStart", "onManagerStart", and "onWorkerStart" are triggered in different processes, and they don't
 *    always run in a fixed order.
 * 2. When the server is reloaded (through method call "$server->reload()"), these events are triggered:
 *    a) Events "onBeforeReload" and "onAfterReload" are triggered in the manager process.
 *    b) Events "onWorkerStop" and "onWorkerStart" are triggered in each worker process and task worker process.
 *    Event "onAfterReload" doesn't necessarily happen before/after the "onWorkerStart" events, since they run in
 *    different processes.
 * 3. About "onWorker*" events:
 *    a) Event "onWorkerStart" happens both in worker processes and in task worker processes.
 *    b) Event "onWorkerStop" happens both in worker processes and in task worker processes, when they stop normally
 *       (e.g., on reload or shutdown).
 *    c) Event "onWorkerExit" happens only when a worker process is asked to stop while it still has pending work in
 *       its event loop (e.g., timers or running coroutines); it is not triggered in this example. See example
 *       "interruptible-sleep.php".
 *    d) Event "onWorkerError" happens in the manager process, when a worker process exits abnormally (e.g., crashes).
 * 4. Event "onReceive" is triggered in TCP servers, between events "onConnect" and "onClose". In HTTP and WebSocket
 *    servers, incoming data is delivered to "onRequest"/"onMessage" instead, so "onReceive" is not triggered in this
 *    example. Event "onPacket" is triggered in UDP servers, which have no connections and thus no
 *    "onConnect"/"onClose" events.
 * 5. Event "onRequest" processes HTTP requests (HTTP/1 and HTTP/2). In a WebSocket server, it handles requests that are
 *    not WebSocket handshakes.
 * 6. Event "onTask" is triggered in task worker processes only, while event "onFinish" is triggered in worker processes
 *    only. Event "onFinish" is triggered only when both conditions are met:
 *    a) The task is triggered by method call "$server->task()".
 *    b) Inside the callback function of the "onTask" event, either method call "$server->finish()" is executed (or
 *       "$task->finish()" when option "task_enable_coroutine" is on), or a return statement is used to return
 *       something back.
 * 7. Event "onPipeMessage" is triggered in the target worker process when another worker process sends it a message
 *    through method $server->sendMessage(); here worker #0 sends one to the task worker (#1).
 * 8. There are some events not triggered in this example:
 *    a) Events "onReceive" and "onPacket". See note 4 above.
 *    b) Events "onWorkerExit" and "onWorkerError". See note 3 above.
 *    c) Events "onHandshake", "onOpen", "onMessage", and "onDisconnect". They are to process WebSocket connections.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/server-events.php"
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

// The port number is omitted on purpose, making the server listen on a random unused port; the port picked is
// exposed as $server->port. Nothing outside this script connects to this server except the HTTP request the
// server makes to itself in the "managerStart" callback below.
$server = new Server('127.0.0.1');
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

    // To make an HTTP request to the server itself 50 milliseconds later.
    Timer::after(50, function () use ($server): void {
        printMessage('Make an HTTP request to the server.' . PHP_EOL, true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:' . $server->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    });

    // To reload the HTTP server 100 milliseconds later.
    Timer::after(100, function () use ($server): void {
        printMessage('Reload the server.' . PHP_EOL, true);
        $server->reload();
    });

    // To shut down the HTTP server 150 milliseconds later.
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
