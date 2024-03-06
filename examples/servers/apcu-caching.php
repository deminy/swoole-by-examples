#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we use APCu to count number of HTTP requests processed by each worker process in Swoole. From this
 * example, we can see that APCu caching in Swoole works the same way as in other PHP CLI applications, even when
 * multiple coroutines and multiple processes are involved.
 *
 * Before using APCu with Swoole, we need to install the APCu extension, have it enabled, and have option
 * "apc.enable_cli" set to "1". Dockerfile for the server contains the necessary commands to install and enable APCu:
 *     https://github.com/deminy/swoole-by-examples/blob/master/dockerfiles/server/Dockerfile
 *
 * Here is how to run this example:
 * 1. First, let's make 499 HTTP requests to the server concurrently:
 *        docker compose exec -t client ab -n 499 -c 499 http://server:9513/
 *    With the above command, we make 499 HTTP requests to the server concurrently. The server uses APCu to store the
 *    number of HTTP requests processed by each worker process.
 * 2. Next, let's check the summary of all counters by making another HTTP request to the server:
 *        docker compose exec -t client curl http://server:9513/summary
 *    The server has 3 worker processes to process these requests. Hopefully we can see that each worker process
 *    processes different number of HTTP requests.
 */

use Swoole\Constant;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9513);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 3, // The number of worker processes to process HTTP requests.
    ]
);

$server->on(
    'request',
    function (Request $request, Response $response) use ($server): void {
        if ($request->server['request_uri'] === '/summary') { // Show summary of all counters.
            $output = '';
            foreach (apcu_cache_info()['cache_list'] as $item) { // @phpstan-ignore foreach.nonIterable
                $output .= "{$item['info']}: " . apcu_fetch($item['info']) . PHP_EOL; // @phpstan-ignore-line
            }

            // The output will be like:
            //     counter_0: 46
            //     counter_1: 16
            //     counter_2: 437
            $response->end($output);
        } else { // Increase a counter.
            apcu_inc("counter_{$server->worker_id}");
            $response->end('OK' . PHP_EOL);
        }
    }
);

$server->start();
