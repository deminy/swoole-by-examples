#!/usr/bin/env php
<?php
/**
 * In this example we start a TCP server on port 9504. Please check next section to see how to test the example.
 */

$comments = <<<EOT
This dummy variable is only for documenting purpose.

To test the TCP server, you can create a new Bash session in the client container first using following command:
    docker exec -ti $(docker ps -qf "name=client") bash
Then execute a netcat command in the Bash session to talk to the TCP server:
    nc server 9504
Now you can start typing something and hit the return button to send it to the TCP server. Whatever you type, the TCP
server echos it back.
EOT;

$server = new Swoole\Server("0.0.0.0", 9504, SWOOLE_BASE, SWOOLE_SOCK_TCP);
$server->on(
    "receive",
    function (Swoole\Server $server, int $fd, int $rid, string $data) {
        $server->send($fd, $data);
    }
);
$server->start();
