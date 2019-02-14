<?php
$socket = stream_socket_server("tcp://0.0.0.0:8000",
    $errno, $errstr);
while ($conn = stream_socket_accept($socket)) {
    if (pcntl_fork() == 0) {
        fwrite($conn, "Hello, World!\n");
        stream_socket_shutdown($conn, STREAM_SHUT_RDWR);
        exit(0);
    }
}
