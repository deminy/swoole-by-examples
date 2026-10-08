#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use class \Swoole\Coroutine\Socket, a low-level socket API for coroutines.
 *
 * Class \Swoole\Coroutine\Socket is a thin, coroutine-friendly layer over the operating system's socket API: you
 * create a socket, then bind/listen/accept or connect, and send/receive bytes yourself. Whenever a call has to wait
 * (accept(), connect(), recv(), etc.), only the current coroutine waits. Swoole's higher-level clients and servers
 * are built on the same idea; this class is for when you need full control, e.g., to implement a protocol of your
 * own.
 *
 * In this example, a server socket echoes back whatever it receives, in uppercase, and two client sockets talk to it
 * concurrently. Each message carries a 4-byte length header, since TCP is a byte stream with no message boundaries:
 * methods sendAll() and recvAll() send/receive exactly the given number of bytes.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./misc/coroutine-socket.php"
 */

use Swoole\Coroutine\Socket;
use Swoole\Coroutine\WaitGroup;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// Send one message: a 4-byte length header (big-endian), followed by the message itself.
function sendMessage(Socket $socket, string $message): void
{
    $socket->sendAll(pack('N', strlen($message)) . $message);
}

// Receive one message sent by sendMessage(). Returns null once the other side closes the connection.
function receiveMessage(Socket $socket): ?string
{
    $header = $socket->recvAll(4);
    if (!is_string($header) || strlen($header) !== 4) {
        return null;
    }
    $length = unpack('N', $header)[1]; // @phpstan-ignore offsetAccess.nonOffsetAccessible
    $body   = $socket->recvAll($length);

    // A shorter body means the connection was closed halfway through the message.
    return (is_string($body) && strlen($body) === $length) ? $body : null;
}

run(function (): void {
    $server = new Socket(AF_INET, SOCK_STREAM, 0);
    $server->bind('127.0.0.1', 0); // Port 0 lets the operating system pick an unused port.
    $server->listen();
    $port = $server->getsockname()['port']; // @phpstan-ignore offsetAccess.nonOffsetAccessible

    // The server: accept each connection, and serve it in a coroutine of its own.
    go(function () use ($server): void {
        while (($connection = $server->accept()) instanceof Socket) {
            go(function () use ($connection): void {
                while (($message = receiveMessage($connection)) !== null) {
                    sendMessage($connection, strtoupper($message));
                }
                $connection->close();
            });
        }
    });

    // Two clients, talking to the server concurrently.
    $wg = new WaitGroup(2);
    foreach (['alice', 'bob'] as $name) {
        go(function () use ($name, $port, $wg): void {
            $client = new Socket(AF_INET, SOCK_STREAM, 0);
            $client->connect('127.0.0.1', $port);
            sendMessage($client, "hello from {$name}");
            // A single string per echo statement: the two clients print concurrently, and an echo statement with
            // multiple arguments (one write per argument) could interleave with the other client's output.
            echo "Client {$name} received: " . receiveMessage($client) . PHP_EOL;
            $client->close();
            $wg->done();
        });
    }
    $wg->wait();

    // Closing the listening socket makes the pending accept() call fail, which ends the server's loop.
    $server->close();
    echo 'Done.', PHP_EOL;
});
