#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we use a WebSocket client to send a message to the WebSocket server on port 9504, and print out the
 * reply.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/websocket.php"
 *
 * Here is the source code of the WebSocket server:
 *     https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/websocket.php
 */

use Swoole\Coroutine\Http\Client;

use function Swoole\Coroutine\run;

run(function (): void {
    $client = new Client('server', 9504);
    $client->upgrade('/');
    $client->push('Swoole');
    echo $client->recv()->data, PHP_EOL; // @phpstan-ignore property.nonObject
    $client->close();
});
