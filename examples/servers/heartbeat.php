#!/usr/bin/env php
<?php
/**
 * In this example we show how to detect dead TCP connections with heartbeats.
 *
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=app") bash -c "./servers/heartbeat.php"
 */

use Swoole\Coroutine\Client;
use Swoole\Event;
use Swoole\Process;
use Swoole\Server;

// In this example, we start a TCP server first. When a client is connected, the server checks activities from the
// client every second. If there is no data received from the client within 3 seconds, the server closes the connection.
$serverProcess = new Process(
    function () {
        $server = new Server("127.0.0.1", 9601);
        //TODO: use constants defined in Swoole library instead.
        $server->set(
            [
                "heartbeat_check_interval" => 1,
                "heartbeat_idle_time"      => 3,
            ]
        );
        $server->on("receive", function ($server, $fd, $reactorId, $data) {
            $server->send($fd, "pong");
        });

        $server->start();

        exit();
    },
    true
);
$serverProcess->start();

go(function () {
    // Sleep for 1 second waiting for the TCP server to start.
    co::sleep(1);

    // After the TCP server is started, we make a connection to the server, and send messages to the server every 2
    // seconds for 2 times. Both should receive a valid response from the server.
    $client = new Client(SWOOLE_SOCK_TCP);
    $client->connect("127.0.0.1", 9601);
    for ($i = 0; $i < 2; $i++) {
        $client->send("ping");
        $data = $client->recv();
        if ("pong" == $data) {
            echo "INFO: Successfully received message \"pong\" from server side.\n";
        } else {
            echo "ERROR: Server side should have sent message \"pong\" back.\n";
        }
        co::sleep(2);
    }

    // Then we wait 3 second and send a last message to the server. This time, since the server has closed the
    // connection due to inactivity, we should receive nothing from the server.
    co::sleep(3);
    $client->send("ping");
    $data = $client->recv();
    if ("pong" == $data) {
        echo "ERROR: Server side should have closed the connection and no message received.\n";
    } else {
        echo "INFO: Server side has successfully closed the connection and no message received.\n";
    }
});

// Stop the TCP server once PHP code finishes execution.
register_shutdown_function(function () use ($serverProcess) {
    Process::kill($serverProcess->pid);
});

Event::wait();
