<?php
$socket = new Co\Socket(AF_INET, SOCK_STREAM, 0);
$socket->bind('0.0.0.0', 9999);
$socket->listen(128);
go(function () use ($socket) {
    while (true) {
        $client = $socket->accept();
        go(function () use ($client) {
            $buffer = '';
            do {
                $buffer .= $client->recv(1024, -1);
            } while (!preg_match('/\r?\n\r?\n/', $buffer));

            $client->send("HTTP/1.1 200 OK\n\nHello, World!\n");
            $client->close();
        });
    }
});
