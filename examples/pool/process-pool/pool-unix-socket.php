#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to create a process pool to communicate through Unix socket.
 *
 * With IPC mode SWOOLE_IPC_SOCKET, a process pool can also listen on a Unix socket (method listen() with a "unix:"
 * address) instead of a TCP socket. Each message sent to the socket is handled by one of the worker processes, in the
 * 'message' callback. A message is framed with a 4-byte length header (big-endian, pack('N', ...)) followed by the
 * message itself, and so is each reply, which a worker sends back to the client through method $pool->write(). A Unix
 * socket is a file, so only processes on the same machine (here: in the same container) can connect to it.
 *
 * To show that, the first worker process plays the client once the pool has started: it sends a message to the pool,
 * prints the reply (which comes from another worker process), then shuts the pool down. A process pool in this IPC mode
 * doesn't support coroutines, so the client uses a plain, blocking PHP stream.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./pool/process-pool/pool-unix-socket.php"
 */

use Swoole\Atomic;
use Swoole\Process\Pool;

$socketFile = sys_get_temp_dir() . '/swoole-pool-unix-socket-' . getmypid() . '.sock';

// Counts the worker processes started. An Atomic object is backed by shared memory, so it must be created before the
// worker processes are started, so that all of them share it.
$started = new Atomic();

$pool = new Pool(3, SWOOLE_IPC_SOCKET);

$pool->on('message', function (Pool $pool, string $message): void {
    $id = $pool->getProcess()->id; // @phpstan-ignore property.nonObject
    $pool->write("Hello, {$message}! (from process #{$id})");
});

$pool->on('workerStart', function (Pool $pool, int $workerId) use ($socketFile, $started): void {
    $started->add();

    // The other worker processes return right away, so that they can handle messages.
    if ($workerId !== 0) {
        return;
    }

    // Wait (up to 5 seconds) until all the worker processes have started: shutting the pool down while a worker process
    // is still starting can leave that process running after the pool has stopped.
    for ($i = 0; $i < 500 && $started->get() < 3; $i++) {
        usleep(10_000);
    }

    $client = stream_socket_client("unix://{$socketFile}");
    if ($client === false) {
        echo 'Failed to connect to the pool.', PHP_EOL;
    } else {
        $message = 'Unix socket';
        fwrite($client, pack('N', strlen($message)) . $message);
        /** @var array{1: positive-int} $header */
        $header = unpack('N', (string) fread($client, 4));
        echo 'Reply from the pool: ', fread($client, $header[1]), PHP_EOL;
        fclose($client);
    }

    $pool->shutdown();
});

if (!$pool->listen("unix:{$socketFile}")) {
    exit("Failed to listen on Unix socket {$socketFile}." . PHP_EOL);
}
$pool->start();

@unlink($socketFile); // Remove the socket file once the pool has stopped.
