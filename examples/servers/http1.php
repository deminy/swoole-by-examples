#!/usr/bin/env php
<?php
/**
 * In this example we start an HTTP/1 server. You should be able to hit URL http://127.0.0.1:9501 and check the output.
 */

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->end(
            <<<EOT
                <pre>
                In this example we start an HTTP/1 server.
                </pre>
            EOT
        );
    }
);
$http->start();
