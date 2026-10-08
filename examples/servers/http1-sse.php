#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1.1 server that streams responses using Server-Sent Events (SSE).
 *
 * When people think of real-time text streaming (e.g., how chat applications like ChatGPT stream their answers
 * word by word), WebSockets or other bi-directional protocols often come to mind. However, for uni-directional
 * streaming from the server to the client, plain HTTP/1.1 is enough: the server declares the response as an
 * event stream ("Content-Type: text/event-stream") and keeps writing chunks to the open connection. This is
 * what Server-Sent Events are, and it is the mechanism used by many text-streaming platforms.
 *
 * In Swoole, HTTP response streaming is done with method \Swoole\Http\Response::write(), which sends a chunk
 * of data over the wire immediately (using HTTP chunked transfer encoding) instead of buffering the whole
 * response body. Here each request is answered with 20 SSE events, one every 100 milliseconds, simulating a
 * real-time text streaming experience; the whole response takes about 2 seconds to finish streaming.
 * Since each request is processed in its own coroutine, the single worker process can stream many such
 * responses concurrently: while one response is sleeping between two events, the other connections are served.
 *
 * To show that, the script starts the server, sends a request to it, and prints each event as soon as it arrives,
 * along with the time it arrived. On the client side, option "write_func" of class \Swoole\Coroutine\Http\Client
 * receives the response body chunk by chunk as it arrives, instead of all at once at the end. Then the script shuts
 * the server down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/http1-sse.php"
 *
 * @see https://en.wikipedia.org/wiki/Server-sent_events
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

$server->on('request', function (Request $request, Response $response): void {
    $response->header('Content-Type', 'text/event-stream; charset=utf-8');
    $response->header('Cache-Control', 'no-cache');

    // Send an SSE event every 100 milliseconds. Each write() call pushes the chunk to the client immediately,
    // so the client sees the events arrive one by one while the response is still in progress.
    foreach (range(1, 20) as $i) {
        Coroutine::sleep(0.1);
        $response->write(sprintf('data: %02d', $i) . "\n\n");
    }
    $response->end();
});

// Once the server has started, send a request to it and print each event as it arrives (the "workerStart" callback
// runs in a coroutine).
$server->on('workerStart', function (Server $server, int $workerId): void {
    $start  = microtime(true);
    $client = new Client('127.0.0.1', $server->port);
    $client->set(
        [
            // Called for each chunk of the response body as soon as it arrives. An SSE event ends with an empty line.
            'write_func' => function (Client $client, string $data) use ($start): bool {
                printf('%-7s (received after %.1f seconds)' . PHP_EOL, trim($data), microtime(true) - $start);

                return true;
            },
        ]
    );
    $client->get('/');
    echo "Content-Type of the response: {$client->headers['content-type']}", PHP_EOL;
    $client->close();

    $server->shutdown();
});

$server->start();
