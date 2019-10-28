#!/usr/bin/env php
<?php
/**
 * In this example we start an HTTP/1 server to demonstrate some advanced usages.
 *
 * You can run following curl commands to see different outputs:
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9502"
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9502/servers/http1-static-content.txt"
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9502/servers/non-existing.txt"
 *   docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9502/non-existing.txt"
 */

use Swoole\Constant;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server("0.0.0.0", 9502);
$server->set(
    [
        // For a list of file types that can be served as static content, please check
        //     https://github.com/swoole/swoole-src/blob/master/src/protocol/mime_types.cc
        Constant::OPTION_DOCUMENT_ROOT            => dirname(__DIR__),
        Constant::OPTION_ENABLE_STATIC_HANDLER    => TRUE,
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
                In this example we start an HTTP/1 server to demonstrate some advanced usages.
                </pre>

            EOT
        );
    }
);
$server->start();
