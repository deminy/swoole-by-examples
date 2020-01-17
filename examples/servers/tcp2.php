#!/usr/bin/env php
<?php
/**
 * In this example we start a TCP server in coroutine style on port 9507. Please check next section to see how to
 * test the example.
 */

$comments = <<<EOT
This dummy variable is only for documenting purpose.

To test the TCP server, you can create a new Bash session in the client container first using following command:
    docker exec -ti $(docker ps -qf "name=client") bash
Then execute a netcat command in the Bash session to talk to the TCP server:
    nc server 9507
Now you can start typing something and hit the return button to send it to the TCP server. Whatever you type, the TCP
server echos it back.
EOT;

use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;

co\run(function () {
    $server = new Server("0.0.0.0", 9507);
    $server->handle(function (Connection $conn) {
        while (true) {
            if ($data = $conn->recv()) {
                $conn->send($data);
            } else {
                $conn->close();
                break;
            }
        }
    });
    $server->start();
});
