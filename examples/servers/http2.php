#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/2 server.
 *
 * You can run following curl command to check HTTP/2 response headers and body:
 *     docker compose exec -t client bash -c "curl -i --http2-prior-knowledge http://server:9503"
 */

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9503, SWOOLE_BASE);
$server->set(
    [
        'open_http2_protocol' => true,
    ]
);
$server->on(
    'request',
    function (Request $request, Response $response): void {
        $response->end(
            <<<'EOT'
                In this example we start an HTTP/2 server.

            EOT
        );
    }
);
$server->start();
