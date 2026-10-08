#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to load new code into a running server without stopping it, using method $server->reload().
 *
 * A Swoole server loads your code once and keeps it in memory, so editing a PHP file has no effect until the code is
 * loaded again. Method $server->reload() tells every worker process to restart: each one finishes the requests it is
 * handling first (for up to "max_wait_time" seconds, 3 by default; then it is killed), then exits and is replaced by a
 * new worker process, which runs the "onWorkerStart" callback again.
 * In SWOOLE_PROCESS mode (used here), the master process keeps accepting connections during a reload, so no request is
 * lost. In SWOOLE_BASE mode (the default), connections that arrive while the old worker processes are finishing their
 * requests may be dropped.
 *
 * What gets reloaded depends on where the code is loaded:
 *   * Files included inside the "onWorkerStart" callback are loaded again by each new worker process, so changes to
 *     them take effect after a reload. Put your application code there.
 *   * Files loaded before $server->start() (like this script itself) are loaded once, before the worker processes are
 *     created. Every worker process inherits them, so they stay the same until the whole server is restarted.
 * A reload can also be triggered from outside the server, by sending signal SIGUSR1 to the manager process (its
 * process ID is $server->manager_pid); signal SIGUSR2 reloads the task worker processes only.
 *
 * In this example, the request handler lives in a separate file that is loaded inside the "onWorkerStart" callback.
 * The script makes a request, updates the handler file, reloads the server, then makes another request: the response
 * comes from the updated code and a new worker process. The script is driven by a user process added through method
 * $server->addProcess(); user processes are not restarted by a reload.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/hot-reload.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Process;

// The request handler, in a file of its own. In a real application, this would be your application code.
$handlerFile = sys_get_temp_dir() . '/swoole-hot-reload-example-' . getmypid() . '.php';
function writeHandler(string $file, string $version): void
{
    file_put_contents($file, "<?php return fn (): string => 'Hello from {$version} of the code (worker process ' . getmypid() . ')';\n");
}
writeHandler($handlerFile, 'version 1');

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0, SWOOLE_PROCESS);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

$server->on('workerStart', function (Server $server, int $workerId) use ($handlerFile): void {
    // Loaded again by every new worker process, including the ones started by a reload.
    $GLOBALS['handler'] = require $handlerFile;
});

$server->on('request', function (Request $request, Response $response): void {
    $response->end($GLOBALS['handler']()); // @phpstan-ignore callable.nonCallable
});

$driver = new Process(
    function () use ($server, $handlerFile): void {
        $get = function () use ($server): string {
            $client = new Client('127.0.0.1', $server->port);
            $client->get('/');
            $client->close();

            return ($client->statusCode === 200) ? (string) $client->body : '(request failed)';
        };

        $before = $get();
        echo 'Before the reload: ', $before, PHP_EOL;

        writeHandler($handlerFile, 'version 2');
        echo 'Handler file updated; reloading the server.', PHP_EOL;
        $server->reload();

        // Wait (up to 3 seconds) until the new worker process answers with the updated code.
        $after = $before;
        for ($i = 0; $i < 30 && !str_contains($after, 'version 2'); $i++) {
            Coroutine::sleep(0.1);
            $after = $get();
        }
        echo 'After the reload:  ', $after, PHP_EOL;

        $server->shutdown();
    },
    false,
    SOCK_DGRAM,
    true // Run the callback inside a coroutine.
);
$server->addProcess($driver);

$server->start();
unlink($handlerFile);
