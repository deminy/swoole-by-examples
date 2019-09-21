#!/usr/bin/env php
<?php
/**
 * In this example we start an HTTP/1 server. You should be able to hit URL http://127.0.0.1:9501 and check the output.
 *
 * You can also run following curl command to check HTTP headers:
 *     curl -i http://127.0.0.1:9501
 */

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server("0.0.0.0", 9501);
$server->on(
    "request",
    function (Request $request, Response $response) {
        $response->end(
            <<<EOT
                <pre>
                In this example we start an HTTP/1 server.
                </pre>

            EOT
        );
    }
);
$server->start();
