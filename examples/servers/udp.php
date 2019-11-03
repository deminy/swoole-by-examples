#!/usr/bin/env php
<?php
/**
 * In this example we start a UDP server on port 9506. Please check next section to see how to test the example.
 */

$comments = <<<EOT
This dummy variable is only for documenting purpose.

To test the UDP server, you can create a new Bash session in the client container first using following command:
    docker exec -ti $(docker ps -qf "name=client") bash
Then execute a netcat command in the Bash session to talk to the UDP server:
    nc -u server 9506
Now you can start typing something and hit the return button to send it to the UDP server. Whatever you type, the UDP
server echos it back.
EOT;

use Swoole\Server;

$server = new Server("0.0.0.0", 9506, SWOOLE_BASE, SWOOLE_SOCK_UDP);
$server->on(
    "packet",
    function (Server $server, string $data, array $clientInfo) {
        $server->sendto($clientInfo["address"], $clientInfo["port"], $data);
    }
);
$server->start();
