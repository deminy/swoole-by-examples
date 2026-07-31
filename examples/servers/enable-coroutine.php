#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows the different ways to enable and disable coroutine support in a Swoole server environment.
 *
 * There are three independent server settings involved:
 * 1. "enable_coroutine" (default: true): whether event callbacks (like "onRequest" and "onWorkerStart") each run
 *    inside a freshly created coroutine. When turned off, callbacks run outside coroutine context (coroutine ID
 *    is -1), and coroutine-only APIs throw errors there. Coroutines can still be created manually, though.
 * 2. "task_enable_coroutine" (default: false): whether "onTask" callbacks run inside coroutines. When turned on,
 *    the "onTask" callback receives a \Swoole\Server\Task object instead of the classic four arguments, and the
 *    task must be finished with `$task->finish()` (a return statement no longer finishes it).
 * 3. "hook_flags" (default: 0): the runtime hooks to enable in each worker process. Unlike in a standalone
 *    process (where `Swoole\Coroutine\run()` enables all hooks implicitly; see example
 *    "csp/coroutines/enable-and-disable.php"), a server enables NO runtime hooks unless this setting is set.
 *
 * The three settings are independent of each other. To demonstrate that, this server is configured with
 * "enable_coroutine" turned OFF while "task_enable_coroutine" and "hook_flags" are turned ON:
 * - "onWorkerStart" runs outside a coroutine in the event worker, but inside one in the task worker.
 * - "onRequest" runs outside a coroutine, but can still create coroutines manually (fire-and-forget only:
 *   without a surrounding coroutine, the callback has no way to wait for them). Three such coroutines are
 *   created, each sleeping for 1 second using the plain PHP sleep(); the time spent in total is still about
 *   1 second only, showing that the coroutines do not block each other (and that the runtime hooks work).
 * - "onTask" runs inside a coroutine and receives a \Swoole\Server\Task object.
 * - Runtime hooks are applied in the worker processes regardless of "enable_coroutine" being off.
 *
 * NOTES:
 * 1. Coroutine-style servers (e.g., \Swoole\Coroutine\Http\Server) have no "enable_coroutine" setting and need
 *    none: they must be started inside a coroutine (e.g., wrapped in `Swoole\Coroutine\run()`), and every
 *    connection is then handled in its own coroutine. Please check example "servers/tcp-coroutine-style.php".
 * 2. For per-process coroutine support in \Swoole\Process and \Swoole\Process\Pool workers, please check
 *    examples "cronjobs/user-process.php" and "cronjobs/process-pool.php".
 *
 * Like example "server-events.php", this script drives itself: it makes an HTTP request to itself, and shuts
 * itself down once the task round trip and the three sleeping coroutines have all completed, finishing in
 * about 1 second.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/enable-coroutine.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Runtime;
use Swoole\Server\Task;
use Swoole\Timer;

use function Swoole\Coroutine\go;

function inCoroutine(): string
{
    return (Coroutine::getCid() !== -1) ? 'yes' : 'no';
}

// The server shuts itself down once all four pieces of self-driving work have completed: the three manually
// created coroutines in "onRequest" plus the task round trip. Everything counted here happens in the (only)
// event worker process, so a static counter is sufficient.
function markDone(Server $server): void
{
    static $pending = 4;
    $pending--;
    if ($pending === 0) {
        $server->shutdown();
    }
}

// The port number is omitted on purpose, making the server listen on a random unused port; the port picked is
// exposed as $server->port. Nothing outside this script connects to this server except the HTTP request the
// server makes to itself in the "managerStart" callback below.
$server = new Server('127.0.0.1');
$server->set(
    [
        Constant::OPTION_WORKER_NUM            => 1,     // One event worker process to process HTTP requests.
        Constant::OPTION_TASK_WORKER_NUM       => 1,     // One task worker process to process tasks.
        Constant::OPTION_ENABLE_COROUTINE      => false, // Event callbacks run OUTSIDE coroutines (default: true).
        Constant::OPTION_TASK_ENABLE_COROUTINE => true,  // "onTask" callbacks run INSIDE coroutines (default: false).
        Constant::OPTION_HOOK_FLAGS            => SWOOLE_HOOK_ALL, // Enable all runtime hooks in worker processes (default: 0).
    ]
);

$server->on('managerStart', function (Server $server): void {
    // To make an HTTP request to the server itself 100 milliseconds later.
    Timer::after(100, function () use ($server): void {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:' . $server->port);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    });

    // A safety net: shut the server down after 5 seconds in case something goes wrong. Under normal execution,
    // the server is shut down through markDone() way earlier, and this timer never fires.
    Timer::after(5000, function () use ($server): void {
        $server->shutdown();
    });
});

$server->on('workerStart', function (Server $server, int $workerId): void {
    // Event "onWorkerStart" is coroutine-wrapped based on "enable_coroutine" in event workers, but based on
    // "task_enable_coroutine" in task workers - here the two settings differ, so the two workers differ too.
    //
    // NOTE: in this script, each echo statement takes a single string only, making it a single atomic write:
    // the event worker and the task worker print to the same output, and a multi-argument echo (one write per
    // argument) from the two processes could interleave halfway through a line.
    $type = $server->taskworker ? 'task' : 'event';
    echo "\"onWorkerStart\" in the {$type} worker runs in a coroutine: " . inCoroutine() . PHP_EOL;

    if (!$server->taskworker) {
        // "hook_flags" is independent of "enable_coroutine": the hooks are applied when each worker process
        // starts, even though the event callbacks of this server run outside coroutines.
        echo 'Runtime hooks are applied in workers even with enable_coroutine off: '
            . ((Runtime::getHookFlags() === SWOOLE_HOOK_ALL) ? 'true' : 'false') . PHP_EOL;
    }
});

$server->on('request', function (Request $request, Response $response) use ($server): void {
    echo '"onRequest" runs in a coroutine: ' . inCoroutine() . PHP_EOL;

    // "enable_coroutine: false" only stops the SERVER from wrapping callbacks in coroutines; creating them
    // manually still works. Note that they are fire-and-forget: this non-coroutine callback cannot wait for
    // them (e.g., \Swoole\Coroutine\WaitGroup and channels require a coroutine context to block in). Instead,
    // each coroutine reports its own completion through markDone(), and the last one measures the time spent.
    //
    // Each of the three coroutines sleeps for 1 second using the plain PHP sleep(), which is non-blocking here
    // thanks to the "hook_flags" setting. Still, the time spent in total is about 1 second only, since the
    // coroutines do not block each other.
    $start = microtime(true);
    for ($i = 1; $i <= 3; $i++) {
        go(function () use ($server, $start, $i): void {
            if ($i === 1) {
                echo 'Coroutines can still be created manually in "onRequest": ' . inCoroutine() . PHP_EOL;
            }
            sleep(1);
            if ($i === 3) {
                // The three coroutines sleep concurrently, and (having slept for the same amount of time) they
                // are resumed in creation order, so this third coroutine is the last one to finish.
                echo 'Three coroutines slept for 1 second each; time spent in total: about '
                    . intval(round(microtime(true) - $start)) . ' second (the coroutines do not block each other).' . PHP_EOL;
            }
            markDone($server);
        });
    }

    $server->task('Hello, Task!');
    $response->end('OK');
});

$server->on('task', function (Server $server, Task $task): void {
    echo '"onTask" runs in a coroutine: ' . inCoroutine() . PHP_EOL;
    echo 'Task data is delivered as a ' . get_class($task) . ' object.' . PHP_EOL;

    // With "task_enable_coroutine" on, a return statement no longer finishes the task; finish() must be called.
    $task->finish($task->data);
});

$server->on('finish', function (Server $server, int $taskId, string $data): void {
    // The task has made the full round trip.
    markDone($server);
});

$server->start();
