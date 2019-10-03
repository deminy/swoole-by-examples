#!/usr/bin/env php
<?php
/**
 * In this example we start a TCP server on port 9504. Please check next section to see how to test the example.
 */

$comments = <<<EOT
This dummy variable is only for documenting purpose.

You may use netcat to open a TCP connection, like:

nc 127.0.0.1 9504

Then you can start typing something and hit the return button to send it to the TCP server. Whatever you type, it will
be sent back from the TCP server.
EOT;

$server = new Swoole\Server("0.0.0.0", 9504, SWOOLE_BASE, SWOOLE_SOCK_TCP);
$server->on(
    "receive",
    function (Swoole\Server $server, int $fd, int $rid, string $data) {
        $server->send($fd, $data);
    }
);
$server->start();
