#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * APCu is a PHP extension that keeps key-value data in shared memory, so the data stays available across requests
 * (and, in Swoole, across worker processes). In this example we use APCu to count the number of HTTP requests processed
 * by each worker process in Swoole. From this example, we can see that APCu caching in Swoole works the same way as in
 * other PHP CLI applications, even when multiple coroutines and multiple processes are involved.
 *
 * The APCu cache is created in shared memory when PHP starts (when the APCu extension is loaded), before the server
 * creates its worker processes, so all the worker processes share the same cache: each worker process increments its
 * own counter (e.g., "counter_0" for worker #0), and any worker process can read all the counters.
 *
 * To show how it works, the script starts a server with 3 worker processes, then a separate process sends 99 HTTP
 * requests to it concurrently, and finally asks the server for a summary of all the counters. Each worker process
 * handles a different number of requests, but the counters add up to 99. Then the script shuts the server down.
 *
 * Before using APCu with Swoole, we need to install the APCu extension, have it enabled, and have option
 * "apc.enable_cli" set to "1". Dockerfile for the server contains the necessary commands to install and enable APCu:
 *     https://github.com/deminy/swoole-by-examples/blob/master/dockerfiles/server/Dockerfile
 *
 * APCu is installed only in the server container, so this example must run from there.
 *
 * How to run this script:
 *     docker compose exec -t server bash -c "./servers/apcu-caching.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\WaitGroup;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Process;

use function Swoole\Coroutine\go;

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 3, // The number of worker processes to process HTTP requests.
    ]
);

$server->on(
    'request',
    function (Request $request, Response $response) use ($server): void {
        if ($request->server['request_uri'] === '/summary') { // Show summary of all counters.
            $counters = [];
            foreach (apcu_cache_info()['cache_list'] as $item) { // @phpstan-ignore foreach.nonIterable
                /** @var array{info: string} $item */
                $count                    = apcu_fetch($item['info']);
                $counters[$item['info']]  = is_int($count) ? $count : 0;
            }
            ksort($counters);

            $output = '';
            foreach ($counters as $name => $count) {
                $output .= "{$name}: {$count}" . PHP_EOL;
            }
            $output .= 'Total: ' . array_sum($counters) . PHP_EOL;

            // The output will be like:
            //     counter_0: 46
            //     counter_1: 16
            //     counter_2: 37
            //     Total: 99
            $response->end($output);
        } else { // Increase a counter.
            apcu_inc("counter_{$server->worker_id}");
            $response->end('OK' . PHP_EOL);
        }
    }
);

// A separate process that sends 99 HTTP requests to the server concurrently, then asks for the summary.
$client = new Process(
    function () use ($server): void {
        $wg = new WaitGroup(99);
        for ($i = 0; $i < 99; $i++) {
            go(function () use ($server, $wg): void {
                $client = new Client('127.0.0.1', $server->port);
                $client->get('/');
                $client->close();
                $wg->done();
            });
        }
        $wg->wait();

        $client = new Client('127.0.0.1', $server->port);
        $client->get('/summary');
        echo $client->body;
        $client->close();

        $server->shutdown();
    },
    false,
    SOCK_DGRAM,
    true // Run the callback inside a coroutine.
);
$server->addProcess($client);

$server->start();
