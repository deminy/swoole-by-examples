#!/usr/bin/env php
<?php
/**
 * In this example we start a WebSocket server on port 9504. Please check next section to see how to test the example.
 */

$comments = <<<EOT
This dummy variable is only for documenting purpose.

If you hit the WebSocket port with a curl command like following (using the HTTP/1 protocol), you will get an HTTP 400
response (Bad Request):
    docker exec -t $(docker ps -qf "name=client") bash -c "curl -i http://server:9504"

If you only want to check if the WebSocket server is running or not, you can do it with following curl command:
    docker exec -ti $(docker ps -qf "name=client") bash -c \
        'curl -iN -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Key: dummy" http://server:9504'
If the WebSocket server runs properly, you should see following response:
    HTTP/1.1 101 Switching Protocols
    Upgrade: websocket
    Connection: Upgrade
    Sec-WebSocket-Accept: fyOX+TXnZhESg2+JC+2rzbH2TpY=
    Sec-WebSocket-Version: 13
    Server: swoole-http-server

To test the WebSocket server, you can create a new Bash session in the client container first using following command:
    docker exec -ti $(docker ps -qf "name=client") bash
Then execute a websocat command in the Bash session to talk to the WebSocket server:
    websocat ws://server:9504
Now you can start typing something and hit the return button to send it to the WebSocket server. Whatever you type, the
WebSocket server will send a hello message back.
EOT;

$server = new Swoole\WebSocket\Server("0.0.0.0", 9504, SWOOLE_BASE);
$server->on(
    "message",
    function (Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame) {
        $server->push($frame->fd, "Hello, {$frame->data}");
    }
);
$server->start();
