#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how to detect dead TCP connections with heartbeats.
 *
 * How to run this script:
 *     docker compose exec -t server bash -c "./servers/heartbeat.php"
 */

use Swoole\Constant;
use Swoole\Coroutine\Client;
use Swoole\Event;
use Swoole\Process;
use Swoole\Server;

use function Swoole\Coroutine\go;

// In this example, we start a TCP server first. When a client is connected, the server checks activities from the
// client every second. If there is no data received from the client within 3 seconds, the server closes the connection.
$serverProcess = new Process(
    function (): never {
        $server = new Server('127.0.0.1', 9601);
        $server->set(
            [
                Constant::OPTION_HEARTBEAT_CHECK_INTERVAL => 1,
                Constant::OPTION_HEARTBEAT_IDLE_TIME      => 3,
            ]
        );
        $server->on(
            'start',
            function (Server $server): void {
                file_put_contents('/var/run/sw-heartbeat.pid', $server->master_pid);
            }
        );
        $server->on(
            'receive',
            function (Server $server, int $fd, int $reactorId, string $data): void {
                $server->send($fd, 'pong');
            }
        );
        $server->on(
            'close',
            function (Server $server, int $fd): void {
                // In this example we only have one client created, and this client will be closed by the server due to
                // timeout.
                echo PHP_EOL, "TCP client #{$fd} is closed due to timeout.", PHP_EOL;
            }
        );
        $server->start();

        exit;
    },
    false
);
$serverProcess->start();

go(function (): void {
    // Sleep for 1 second waiting for the TCP server to start.
    sleep(1);

    // After the TCP server is started, we make a connection to the server, and send messages to the server every 2
    // seconds for 2 times. Both should receive a valid response from the server.
    $client = new Client(SWOOLE_SOCK_TCP);
    $client->connect('127.0.0.1', 9601);
    for ($i = 0; $i < 2; $i++) {
        $client->send('ping');
        $data = $client->recv();
        if ($data == 'pong') {
            echo 'INFO: Successfully received message "pong" from server side.', PHP_EOL;
        } else {
            echo 'ERROR: Server side should have sent message "pong" back.', PHP_EOL;
        }
        sleep(2);
    }

    // Then we wait 2 second and send a last message to the server. This message is sent 4 seconds after last message,
    // thus the server has closed the connection due to inactivity and we should receive nothing from the server.
    sleep(2);
    $client->send('ping');
    $data = $client->recv();
    if ($data == 'pong') {
        echo PHP_EOL, 'ERROR: Server side should have closed the connection and no message received.', PHP_EOL;
    } else {
        echo PHP_EOL, 'INFO: Server side has successfully closed the connection and no message received.', PHP_EOL;
    }
});

// Stop the TCP server and the process created once PHP code finishes execution.
register_shutdown_function(function () use ($serverProcess): void {
    Process::kill(intval(shell_exec('cat /var/run/sw-heartbeat.pid')), SIGTERM);
    sleep(1);
    Process::kill($serverProcess->pid);
});

// NOTE: In most cases it's not necessary nor recommended to use method `Swoole\Event::wait()` directly in your code.
// The example in this file is just for demonstration purpose.
Event::wait();
