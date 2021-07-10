#!/usr/bin/env php
<?php

/**
 * In this example we start an HTTP/1 server, with following features supported:
 *     * customize status code.
 *     * server static content.
 *     * support gzip compression.
 *
 * You can run following curl commands to check HTTP/1 response headers and body:
 *   # To check customized status code and reason in HTTP response headers.
 *   docker exec -t $(docker ps -qf "name=client") bash -c \
 *       "curl -i http://server:9501"
 *
 *   # To test gzip support by hitting the same URL with HTTP header "Accept-Encoding: gzip" included.
 *   docker exec -t $(docker ps -qf "name=client") bash -c \
 *       "curl -i -H 'Accept-Encoding: gzip' --compressed http://server:9501"
 *
 *   # To fetch an existing file under one of the specified static file locations in the web server.
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9501/servers/http1-static-content.txt"
 *
 *   # To fetch a non-existing file under one of the specified static file locations in the web server.
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9501/servers/non-existing.txt"
 *
 *   # To fetch a non-existing file outside of the specified static file locations in the web server.
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9501/non-existing.txt"
 *
 * For advanced usages like integrated cronjob and job queue, please check script http1-integrated.php.
 */

use Swoole\Constant;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server("0.0.0.0", 9501);

// All the options set here are optional.
$server->set(
    [
        Constant::OPTION_HTTP_COMPRESSION       => true,
        Constant::OPTION_HTTP_COMPRESSION_LEVEL => 5,

        // For a list of file types that can be served as static content, please check
        //     https://github.com/swoole/swoole-src/blob/master/src/protocol/mime_types.cc
        Constant::OPTION_DOCUMENT_ROOT            => dirname(__DIR__),
        Constant::OPTION_ENABLE_STATIC_HANDLER    => true,
        Constant::OPTION_STATIC_HANDLER_LOCATIONS => [
            "/clients",
            "/servers",
        ],
    ]
);

$server->on(
    "request",
    function (Request $request, Response $response) {
        // Next method call is to show how to change HTTP status code from the default one (200) to something else.
        $response->status(234, 'Test');

        $response->end(
            <<<EOT
                <pre>
                In this example we start an HTTP/1 server.

                NOTE: The autoreloading feature is enabled. If you update this PHP script and
                then refresh URL http://127.0.0.1:9501, you should see the changes made.
                </pre>

            EOT
        );
    }
);
$server->start();
