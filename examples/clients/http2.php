#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we use an HTTP/2 client to talk to the HTTP/2 server started in the "server" container.
 *
 * Unlike HTTP/1, where a connection can only carry one request/response exchange at a time, HTTP/2 multiplexes
 * concurrent requests as streams over a single TCP connection. Here three requests are sent back to back over the
 * same connection before any response is read, and the responses are then received one by one. Client-initiated
 * streams use odd stream IDs, so the three requests go out on streams #1, #3, and #5.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/http2.php"
 *
 * Here is the source code of the HTTP/2 server:
 *     https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http2.php
 */

use Swoole\Coroutine\Http2\Client;
use Swoole\Http2\Request;

use function Swoole\Coroutine\run;

run(function (): void {
    $client = new Client('server', 9503);
    $client->connect();

    // Send three requests over the same connection, without waiting for any response in between. Method send()
    // returns the ID of the newly-created stream.
    for ($i = 1; $i <= 3; $i++) {
        $request         = new Request();
        $request->method = 'GET';
        $request->path   = "/?request={$i}";
        echo "Request #{$i} sent on stream #", $client->send($request), '.', PHP_EOL; // @phpstan-ignore echo.nonString
    }

    // Receive the three responses. Method recv() returns whichever response arrives next; the stream ID on the
    // response object tells which request it belongs to.
    for ($i = 0; $i < 3; $i++) {
        $response = $client->recv();
        if ($response === false) {
            throw new RuntimeException('Failed to receive a response.');
        }
        // The response body is available in property "data" of the response object.
        echo "Received a response on stream #{$response->streamId} with status code {$response->statusCode}.", PHP_EOL; // @phpstan-ignore property.nonObject, property.nonObject
    }

    $client->close();
});
