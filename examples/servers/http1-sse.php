#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1.1 server that streams responses using Server-Sent Events (SSE) on port
 * 9515.
 *
 * When people think of real-time text streaming (e.g., how chat applications like ChatGPT stream their answers
 * word by word), WebSockets or other bi-directional protocols often come to mind. However, for uni-directional
 * streaming from the server to the client, plain HTTP/1.1 is enough: the server declares the response as an
 * event stream ("Content-Type: text/event-stream") and keeps writing chunks to the open connection. This is
 * what Server-Sent Events are, and it is the mechanism used by many text-streaming platforms.
 *
 * In Swoole, HTTP response streaming is done with method \Swoole\Http\Response::write(), which sends a chunk
 * of data over the wire immediately (using HTTP chunked transfer encoding) instead of buffering the whole
 * response body. Here each request is answered with 60 SSE events, one every 60 milliseconds, simulating a
 * real-time text streaming experience; the whole response takes about 3.6 seconds to finish streaming.
 * Since each request is processed in its own coroutine, the single worker process can stream many such
 * responses concurrently: while one response is sleeping between two events, the other connections are served.
 *
 * This script is managed by Supervisord inside the "server" container, thus there is no need to start it manually.
 *
 * To watch the events arrive one by one, run following command in a different terminal (option -N disables
 * output buffering in curl):
 *     docker compose exec -ti client curl -N http://server:9515
 *
 * @see https://en.wikipedia.org/wiki/Server-sent_events
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9515);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

$server->on('request', function (Request $request, Response $response): void {
    $response->header('Content-Type', 'text/event-stream; charset=utf-8');
    $response->header('Cache-Control', 'no-cache');

    // Send an SSE event every 60 milliseconds. Each write() call pushes the chunk to the client immediately,
    // so the client sees the events arrive one by one while the response is still in progress.
    foreach (range(1, 60) as $i) {
        Coroutine::sleep(0.060);
        $response->write(sprintf('data: %02d', $i) . "\n\n");
    }
    $response->end();
});

$server->start();
