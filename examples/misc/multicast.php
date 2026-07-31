#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to make a UDP server join an IP multicast group, so that it receives datagrams sent
 * to the multicast address (and not just those sent to its own address).
 *
 * Multicast membership is not a Swoole feature by itself: the server's underlying socket is fetched with
 * method \Swoole\Server::getSocket(), and standard PHP socket functions are used to join the multicast group
 * (option MCAST_JOIN_GROUP of function socket_set_option()).
 *
 * To keep the example self-contained, once the server is up, a UDP datagram is sent to the multicast group
 * address (not to the server address) from a client created inside the "workerStart" event callback. The
 * server receives it through its multicast membership, prints it out, and then shuts down. The datagram never
 * mentions the server address, which proves the multicast membership is in effect. To see that the membership
 * really is what makes the delivery work, comment out the socket_set_option() call and rerun the script: the
 * server then never receives the datagram, and the script waits forever.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./misc/multicast.php"
 */

use Swoole\Constant;
use Swoole\Server;

// The IP address of the multicast group to join. Addresses in the 224.0.0.0/4 block are multicast addresses.
const MULTICAST_GROUP = '224.10.20.30';

// Port 0 makes the server listen on a random unused port; it is passed explicitly only because the server mode
// and the socket type after it need to be specified. Nothing outside this script connects to this server.
$server = new Server('0.0.0.0', 0, SWOOLE_BASE, SWOOLE_SOCK_UDP);
$server->set([Constant::OPTION_WORKER_NUM => 1]);

$socket = $server->getSocket();
$joined = socket_set_option(
    $socket, // @phpstan-ignore argument.type
    IPPROTO_IP,
    MCAST_JOIN_GROUP,
    [
        'group'     => MULTICAST_GROUP, // The multicast group address to join.
        'interface' => 0,               // 0 means the default network interface; a name (e.g., eth0) or an index also works.
    ]
);
if ($joined === false) {
    throw new RuntimeException('Unable to join the multicast group.');
}

$server->on('workerStart', function (Server $server): void {
    // Send a UDP datagram to the multicast group (not to the server address directly). The server port is
    // random (see above), so it is read back from property $server->port.
    $client = stream_socket_client(sprintf('udp://%s:%d', MULTICAST_GROUP, $server->port));
    if ($client === false) {
        throw new RuntimeException('Unable to create the UDP client.');
    }
    fwrite($client, 'Hello, multicast group ' . MULTICAST_GROUP . '!');
    fclose($client);
});

$server->on('packet', function (Server $server, string $data, array $addr): void {
    echo "Datagram received through the multicast group: {$data}", PHP_EOL;
    $server->shutdown();
});

$server->start();
