#!/usr/bin/env php
<?php
/**
 *

In this example we start a WebSocket server on port 9503.

If you hit that port with command "curl -i http://127.0.0.1:9503" (using the HTTP/1 protocol), you will get an HTTP 400
response (Bad Request).

To test the WebSocket server, you can install websocat and use following command to send and receive messages:
    websocat ws://127.0.0.1:9503
websocat can be downloaded from following URL:
    https://github.com/vi/websocat

If you only want to check if the WebSocket server is running or not, you can do it with following curl command:
    curl \
        --include \
        --no-buffer \
        --header "Connection: Upgrade" \
        --header "Upgrade: websocket" \
        --header "Host: 127.0.0.1" \
        --header "Origin: http://127.0.0.1" \
        --header "Sec-WebSocket-Key: SGVsbG8sIHdvcmxkIQ==" \
        --header "Sec-WebSocket-Version: 13" \
        http://127.0.0.1:9503
If the WebSocket server runs properly, you should see following response:
    HTTP/1.1 101 Switching Protocols
    Upgrade: websocket
    Connection: Upgrade
    Sec-WebSocket-Accept: qGEgH3En71di5rrssAZTmtRTyFk=
    Sec-WebSocket-Version: 13
    Server: swoole-http-server

 */

use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;

$server = new Server("0.0.0.0", 9503, SWOOLE_BASE);
$server->on(
    "message",
    function (Server $server, Frame $frame) {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);
$server->start();
