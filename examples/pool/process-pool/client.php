#!/usr/bin/env php
<?php
/**
 * In this script we show how to communicate with process pool in three different ways:
 *     * through message queue.
 *     * through TCP socket.
 *     * through Unix socket.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=server") bash -c "./pool/process-pool/client.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Client;
use Swoole\MsgQueue;

$settings = [
    Constant::OPTION_OPEN_LENGTH_CHECK     => 1,
    Constant::OPTION_PACKAGE_LENGTH_TYPE   => "N",
    Constant::OPTION_PACKAGE_LENGTH_OFFSET => 0,
    Constant::OPTION_PACKAGE_BODY_OFFSET   => 4,
];

// This first example shows how to send messages (and deploy tasks) to a process pool through message queue.
// Swoole extension "async" (https://github.com/swoole/ext-async) is needed to run this example.
// If your PHP is compiled to support System V semaphore, you can also use message queue functions msg_get_queue() and
// msg_send() to do that. Check PHP manual https://www.php.net/sem for details.
if (class_exists(MsgQueue::class)) {
    go(function () {
        $mq = new MsgQueue(0x7000001);
        for ($i = 0; $i < 3; $i++) {
            $mq->push("Message #{$i}!");
        }
    });
}

// This second example shows how to send messages (and deploy tasks) to a process pool through TCP socket.
go(function () use ($settings) {
    $client = new Client(SWOOLE_SOCK_TCP);
    $client->set($settings);
    $client->connect("server", 9701);
    $requestMessage = "TCP socket";
    $client->send(pack("N", strlen($requestMessage)) . $requestMessage);
    $responseMessage = $client->recv();
    $responseMessage = substr($responseMessage, 4, unpack("N", substr($responseMessage, 0, 4))[1]);
    echo $responseMessage, "\n";
    $client->close();
});

// This third example shows how to send messages (and deploy tasks) to a process pool through Unix socket.
// Since Unix socket is faster than TCP socket (in general), this coroutine should finish first than the previous one.
go(function () use ($settings) {
    $client = new Client(SWOOLE_SOCK_UNIX_STREAM);
    $client->set($settings);
    $client->connect("/var/run/pool-unix-socket.sock");
    $requestMessage = "Unix socket";
    $client->send(pack("N", strlen($requestMessage)) . $requestMessage);
    $responseMessage = $client->recv();
    $responseMessage = substr($responseMessage, 4, unpack("N", substr($responseMessage, 0, 4))[1]);
    echo $responseMessage, "\n";
    $client->close();
});
