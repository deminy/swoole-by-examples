#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start an HTTP/1 server, with the following features supported:
 *     * customize status code.
 *     * serve static content.
 *     * support gzip compression.
 *
 * This server is started automatically in the "server" container (managed by Supervisord), and restarted whenever a PHP
 * file under examples/ changes, so there's no need to start it yourself. Its output can be viewed with:
 *     docker compose logs -f server
 *
 * You can run the following curl commands to check HTTP/1 response headers and body:
 *   # To check customized status code and reason in HTTP response headers.
 *   docker compose exec -t client bash -c "curl -i http://server:9501"
 *
 *   # To test gzip support by hitting the same URL with HTTP header "Accept-Encoding: gzip" included.
 *   docker compose exec -t client bash -c "curl -i -H 'Accept-Encoding: gzip' --compressed http://server:9501"
 *
 *   # To fetch an existing file under one of the specified static file locations in the web server (status 200).
 *   docker compose exec -t client bash -c "curl -i http://server:9501/servers/http1-static-content.moc"
 *
 *   # To fetch a non-existing file under one of the specified static file locations in the web server (status 404).
 *   docker compose exec -t client bash -c "curl -i http://server:9501/servers/non-existing.txt"
 *
 *   # To fetch a non-existing file outside the specified static file locations in the web server (not handled as a
 *   # static file, so the request goes to the "onRequest" callback and gets status 234).
 *   docker compose exec -t client bash -c "curl -i http://server:9501/non-existing.txt"
 *
 *   # The server sleeps first if query parameter "sleep" is given (in seconds); used by examples under hooks/.
 *   docker compose exec -t client bash -c "curl -i 'http://server:9501?sleep=2'"
 *
 * For advanced usages like integrated cron jobs and task workers, please check script http1-integrated.php.
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9501);

// All the options set here are optional.
$server->set(
    [
        Constant::OPTION_DOCUMENT_ROOT            => dirname(__DIR__),
        Constant::OPTION_ENABLE_STATIC_HANDLER    => true,
        Constant::OPTION_STATIC_HANDLER_LOCATIONS => [
            '/clients',
            '/servers',
        ],

        Constant::OPTION_HTTP_COMPRESSION       => true,
        Constant::OPTION_HTTP_COMPRESSION_LEVEL => 5,
    ]
);

$server->on(
    'request',
    function (Request $request, Response $response): void {
        if (!empty($request->get['sleep'])) {
            Coroutine::sleep((float) $request->get['sleep']); // Sleep for a while if HTTP query parameter "sleep" is present.
        }

        // Next method call is to show how to change HTTP status code from the default one (200) to something else.
        $response->status(234, 'Test');

        $response->end(
            <<<'EOT'
                <pre>
                In this example we start an HTTP/1 server.

                NOTE: The autoreloading feature is enabled. If you update this PHP script and
                then send the request again (e.g., run "curl -i http://server:9501" in the
                client container), you should see the changes made.
                </pre>

            EOT
        );
    }
);

// By default, Swoole sets MIME type of static content based on file extension. For example, for file "foo.txt", Swoole
// sets HTTP header "Content-Type" to "text/plain". For a list of file types that can be recognized by Swoole, please
// check file src/protocol/mime_type.cc in the Swoole source code:
//     https://github.com/swoole/swoole-src
//
// For unknown file types, Swoole sets HTTP header "Content-Type" to "application/octet-stream". The following code
// shows how to customize MIME type for "*.moc" files.
swoole_mime_type_add('moc', 'text/plain');

$server->start();
