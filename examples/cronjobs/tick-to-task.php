#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example runs cron-style scheduled jobs inside a web server, combining the two standard in-server patterns:
 *
 *   1. The SCHEDULING side: \Swoole\Timer::tick() registered in the workerStart event, guarded to $workerId === 0.
 *      The guard is essential - workerStart fires in EVERY worker process AND every task worker process (task worker
 *      IDs continue after the regular workers'), so an unguarded timer would fire the job once per process. Checking
 *      for worker #0 makes it run exactly once per server. As a bonus the pattern is self-healing: when worker #0 is
 *      restarted (crash, reload, max_request recycling), workerStart fires again and re-registers the timer - with
 *      freshly reloaded code, which is something user-process.php's dedicated process does NOT get on reload.
 *   2. The EXECUTION side: the tick callback does no real work itself - it only dispatches the job to the task-worker
 *      pool via $server->task(). Worker #0 also serves requests, so a heavy or blocking job in the tick callback
 *      would stall request handling there; task workers are synchronous, blocking-safe processes designed for
 *      exactly this kind of work (note the plain, blocking usleep() in onTask - perfectly fine there), bounded in
 *      concurrency by task_worker_num and crash-isolated (a fatal in a task kills only a task worker, which the
 *      server restarts). The task's return value arrives back in the dispatching worker via the onFinish event.
 *
 * Related caveats documented by the other examples in this folder: timers are per-process and are destroyed with the
 * worker (the interval restarts from zero on recycling, so very long intervals can be skipped), and ticks of a slow
 * dispatch could in principle overlap - kept trivially short here.
 *
 * This demo is self-terminating: worker #0 shuts the server down after ~3.6 seconds, giving the tick time to
 * dispatch three runs. The server listens on port 9541 just because a server must listen somewhere; the port is not
 * used.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./cronjobs/tick-to-task.php"
 */

use Swoole\Constant;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Timer;

$server = new Server('0.0.0.0', 9541, SWOOLE_BASE);
$server->set(
    [
        Constant::OPTION_WORKER_NUM      => 2, // Two regular workers, to show that only worker #0 schedules.
        Constant::OPTION_TASK_WORKER_NUM => 2, // Two task workers, where the scheduled jobs actually execute.
    ]
);

$server->on('workerStart', function (Server $server, int $workerId): void {
    if ($server->taskworker) {
        printf('[%s] Task worker #%d started.%s', date('H:i:s'), $workerId, PHP_EOL);
        return;
    }
    if ($workerId !== 0) {
        printf('[%s] Worker #%d started; the cron timer is NOT registered here.%s', date('H:i:s'), $workerId, PHP_EOL);
        return;
    }

    printf('[%s] Worker #0 started; registering the cron timer here.%s', date('H:i:s'), PHP_EOL);
    Timer::tick(1000, function () use ($server): void {
        // Keep the tick lightweight: dispatch only, never do the real work here.
        static $run = 0;
        $run++;
        printf('[%s] Tick: dispatching run %d to the task workers.%s', date('H:i:s'), $run, PHP_EOL);
        $server->task($run);
    });

    // Demo-bounded: stop the whole server after ~3.6 seconds. The periodic timer must be cleared first: pending
    // timers keep the worker's event loop alive during shutdown, so leaving the tick registered would stall the
    // exit until Swoole force-terminates the worker ("worker exit timeout, forced termination").
    Timer::after(3600, function () use ($server): void {
        Timer::clearAll();
        $server->shutdown();
    });
});
$server->on('task', function (Server $server, int $taskId, int $srcWorkerId, mixed $data): void {
    if (!is_int($data)) { // This example only ever dispatches integer run numbers.
        return;
    }
    printf('[%s] Task worker #%d: running scheduled job (run %d).%s', date('H:i:s'), $server->worker_id, $data, PHP_EOL);
    usleep(300_000); // Simulate slow, BLOCKING work - safe in a task worker, but never in the tick callback.
    $server->finish("run {$data} done");
});
$server->on('finish', function (Server $server, int $taskId, mixed $data): void {
    if (!is_string($data)) { // This example's tasks only ever finish with a string result.
        return;
    }
    printf('[%s] Worker #%d: job result received: %s.%s', date('H:i:s'), $server->worker_id, $data, PHP_EOL);
});
$server->on('request', function (Request $request, Response $response): void {
    $response->end('OK' . PHP_EOL);
});

$server->start();
