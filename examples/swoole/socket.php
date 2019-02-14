<?php
$socket = new Co\Socket(AF_INET, SOCK_STREAM, 0);
$socket->bind('0.0.0.0', 8000);
$socket->listen(128);
go(function () use ($socket) {
    while (true) {
        $client = $socket->accept();
        go(function() use ($client) {
            $client->send("Hello, World!\n");
            $client->close();
        });
    }
});
