#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/2 server.
 *
 * The server speaks HTTP/2 without TLS (also known as "h2c"). curl's option --http2-prior-knowledge tells curl to talk
 * HTTP/2 right away, without first trying HTTP/1.1. Browsers only use HTTP/2 over TLS (see example https.php for TLS).
 * Option "open_http2_protocol" keeps HTTP/1 working on the same port too (see example mixed-protocols-same-port.php).
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. Its output can be viewed with:
 *     docker compose logs -f server
 *
 * You can run the following curl command to check HTTP/2 response headers and body:
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
