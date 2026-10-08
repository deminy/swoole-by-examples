#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we show how to detect dead TCP connections using TCP keepalive. It uses the same kind of TCP echo
 * server as example tcp-event-driven.php, with TCP keepalive enabled and its parameters adjusted.
 *
 * With TCP keepalive enabled, the operating system (not the application) watches each idle connection: once a
 * connection has been idle for "tcp_keepidle" seconds, it sends a keepalive probe to the other side, and repeats that
 * every "tcp_keepinterval" seconds until the other side answers. If "tcp_keepcount" probes in a row go unanswered,
 * the connection is considered dead and closed. A healthy peer answers each probe, so the connection stays open and the
 * idle countdown starts over.
 *
 * In real life, you'd check it by making a connection to the server and then unplugging the network cable on one side,
 * which can't be done in this Docker environment. Instead, the script starts the server with short keepalive settings,
 * connects to it, and then watches the server side of the connection for about 5 seconds, by reading the connection's
 * keepalive timer from file /proc/net/tcp (the same information that command "netstat -o" shows). The output shows the
 * time left before the first keepalive probe counting down to 0. The other side answers the probe, so the connection
 * stays open; after that, the kernel re-checks the connection every "tcp_keepinterval" second and re-arms the timer for
 * the rest of the 3 idle seconds, which is why the numbers after the first 0.0 jump around instead of counting down
 * from 3 again.
 *
 * Compare with example heartbeat.php, where Swoole itself (not the operating system) closes connections that have been
 * idle for too long.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/keepalive.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;
use Swoole\Server;

/**
 * Returns the number of seconds left before the next keepalive probe on the server side of the connection between the
 * two given ports, or null if the connection has no keepalive timer running. In file /proc/net/tcp, ports are in hex,
 * and column "tr:tm->when" holds the timer type (02 for keepalive) and the time left in hundredths of a second.
 */
function keepaliveTimeLeft(int $serverPort, int $clientPort): ?float
{
    $local  = sprintf(':%04X', $serverPort);
    $remote = sprintf(':%04X', $clientPort);
    $lines  = file('/proc/net/tcp') ?: [];
    foreach (array_slice($lines, 1) as $line) {
        $fields = preg_split('/\s+/', trim($line)) ?: [];
        if (str_ends_with($fields[1] ?? '', $local) && str_ends_with($fields[2] ?? '', $remote)) {
            [$type, $time] = explode(':', $fields[5] ?? '00:0');

            return ($type === '02') ? hexdec($time) / 100 : null;
        }
    }

    return null;
}

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0, SWOOLE_BASE);
$server->set(
    [
        Constant::OPTION_WORKER_NUM         => 1,
        Constant::OPTION_OPEN_TCP_KEEPALIVE => true,

        // Following 3 parameters are to adjust timeout. They are much shorter than in real life, so that the output
        // shows a keepalive probe within a few seconds. Run this command to check default values in the container:
        //     docker compose exec -t client bash -c "tail -n +1 /proc/sys/net/ipv4/tcp_keepalive_*"
        Constant::OPTION_TCP_KEEPIDLE       => 3, // Send the first keepalive probe after 3 seconds of idleness.
        Constant::OPTION_TCP_KEEPINTERVAL   => 1, // Then one more probe every second, until the other side answers.
        Constant::OPTION_TCP_KEEPCOUNT      => 3, // Close the connection after 3 unanswered probes in a row.
    ]
);
$server->on(
    'receive',
    function (Server $server, int $fd, int $reactorId, string $data): void {
        $server->send($fd, $data);
    }
);

// Once the server has started, connect to it (the "workerStart" callback runs in a coroutine), then leave the
// connection idle and watch its keepalive timer.
$server->on(
    'workerStart',
    function (Server $server, int $workerId): void {
        $client = new Client(SWOOLE_SOCK_TCP);
        $client->connect('127.0.0.1', $server->port);
        $client->send('ping');
        $reply = $client->recv();
        echo 'Reply from the server: ', is_string($reply) ? $reply : '(none)', PHP_EOL;

        $clientPort = $client->getsockname()['port']; // @phpstan-ignore offsetAccess.nonOffsetAccessible
        for ($i = 0; $i < 10; $i++) {
            $timeLeft = keepaliveTimeLeft($server->port, $clientPort); // @phpstan-ignore argument.type
            echo ($timeLeft === null)
                ? 'No keepalive timer running.' . PHP_EOL
                : sprintf('The next keepalive probe will be sent in %.1f seconds.', $timeLeft) . PHP_EOL;
            Coroutine::sleep(0.5);
        }

        $client->close();
        $server->shutdown();
    }
);

$server->start();
