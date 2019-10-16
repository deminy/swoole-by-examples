#!/usr/bin/env php
<?php
/**
 * In this example we start a WebSocket server on port 9503. Please check next section to see how to test the example.
 */

$comments = <<<EOT
This dummy variable is only for documenting purpose.

If you hit the WebSocket port with command "curl -i http://127.0.0.1:9503" (using the HTTP/1 protocol), you will get an
HTTP 400 response (Bad Request).

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

To test the WebSocket server, you can create a new Bash session in the client container first using following command:
    docker exec -ti $(docker ps -qf "name=client") bash
, then execute a websocat command in the Bash session to talk to the TCP server:
    websocat ws://server:9503
EOT;

$server = new Swoole\WebSocket\Server("0.0.0.0", 9503, SWOOLE_BASE);
$server->on(
    "message",
    function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);
$server->start();
