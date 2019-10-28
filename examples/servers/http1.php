#!/usr/bin/env php
<?php
/**
 * In this example we start an HTTP/1 server.
 *
 * You can run following curl command to check HTTP/1 response headers and body:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9501"
 *
 * To see some advanced usages like customizing status code, serving static content, and more, please check script
 * http1-advanced.php.
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

                NOTE: The autoreloading feature is enabled. If you update this PHP script and
                then refresh URL http://127.0.0.1:9501, you should see the changes made.
                </pre>

            EOT
        );
    }
);
$server->start();
