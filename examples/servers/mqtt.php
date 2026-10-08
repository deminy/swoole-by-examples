#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a minimal MQTT broker.
 *
 * Swoole itself does not implement the MQTT protocol; what the server setting "open_mqtt_protocol" does is
 * packet framing: it makes the server parse MQTT fixed headers, so that each "receive" event carries exactly
 * one complete MQTT control packet. The packets themselves still need to be parsed and answered by the
 * application, which is what this script does. To keep the example small, only the parts of MQTT 3.1.1 needed
 * for a basic publish/subscribe round trip are implemented:
 * - CONNECT is acknowledged with CONNACK, PINGREQ with PINGRESP, and DISCONNECT closes the connection.
 * - SUBSCRIBE is acknowledged with SUBACK, and the subscribed topics are recorded per connection.
 * - A PUBLISH packet (QoS 0 only) is forwarded verbatim to all connections subscribed to its topic (exact
 *   topic matches only; wildcards are not supported).
 * The subscription table is kept in a plain PHP array, which works because the server runs a single worker
 * process; with multiple workers, connections would land in different processes and the table would have to be
 * shared (e.g., in Redis or a \Swoole\Table).
 *
 * To show that the broker works with real MQTT clients, the script starts the broker, then uses the Mosquitto
 * command-line clients (installed only in the client container) in a separate process: mosquitto_sub subscribes to a
 * topic and waits for one message, and mosquitto_pub publishes a message to the same topic. The script prints what the
 * subscriber received, then shuts the broker down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/mqtt.php"
 */

use Swoole\Constant;
use Swoole\Process;
use Swoole\Server;

// MQTT 3.1.1 control packet types (the ones handled by this example).
const MQTT_CONNECT     = 1;
const MQTT_PUBLISH     = 3;
const MQTT_SUBSCRIBE   = 8;
const MQTT_PINGREQ     = 12;
const MQTT_DISCONNECT  = 14;

/**
 * Decodes the variable-length "remaining length" field of an MQTT fixed header, advancing $offset past it.
 */
function decodeRemainingLength(string $data, int &$offset): int
{
    $multiplier = 1;
    $value      = 0;
    do {
        $byte = ord($data[$offset++]);
        $value += ($byte & 0x7F) * $multiplier;
        $multiplier *= 128;
    } while (($byte & 0x80) !== 0);

    return $value;
}

/**
 * Reads a length-prefixed UTF-8 string (2-byte big-endian length followed by the bytes), advancing $offset.
 */
function decodeString(string $data, int &$offset): string
{
    /** @var array{n: int} $unpacked */
    $unpacked = unpack('nn', $data, $offset);
    $length   = $unpacked['n'];
    $string   = substr($data, $offset + 2, $length);
    $offset += 2 + $length;

    return $string;
}

// The subscription table: topic => a list of subscribed connections (as keys of an array).
$subscriptions = [];

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0);
$server->set(
    [
        Constant::OPTION_WORKER_NUM         => 1,    // A single worker process, so the subscription table can be a plain array.
        Constant::OPTION_OPEN_MQTT_PROTOCOL => true, // Frame incoming data into complete MQTT control packets.
    ]
);

$server->on('receive', function (Server $server, int $fd, int $reactorId, string $data) use (&$subscriptions): void {
    $type   = ord($data[0]) >> 4; // High nibble of the first byte is the control packet type.
    $offset = 1;
    decodeRemainingLength($data, $offset); // Advance $offset to the start of the variable header.

    switch ($type) {
        case MQTT_CONNECT:
            // Accept every client: session-present flag 0, return code 0 (connection accepted).
            $server->send($fd, "\x20\x02\x00\x00");
            break;
        case MQTT_SUBSCRIBE:
            $packetId = substr($data, $offset, 2); // The 2-byte packet identifier, echoed back in the SUBACK packet.
            $offset += 2;
            // The payload is a list of topic filters, each followed by a requested QoS byte.
            $grantedQos = '';
            while ($offset < strlen($data)) {
                $topic = decodeString($data, $offset);
                $offset++; // Skip the requested QoS byte; this broker supports QoS 0 only.
                $subscriptions[$topic][$fd] = true;
                $grantedQos .= "\x00"; // Granted QoS 0 for each topic filter.
            }
            $server->send($fd, "\x90" . chr(2 + strlen($grantedQos)) . $packetId . $grantedQos);
            break;
        case MQTT_PUBLISH:
            // This broker supports QoS 0 publishes only: a PUBLISH packet with a higher QoS carries a packet
            // identifier after the topic name and requires a PUBACK/PUBREC handshake, none of which is
            // implemented here, so such packets are ignored. The QoS level lives in bits 1-2 of the first byte.
            if (((ord($data[0]) >> 1) & 0x03) !== 0) {
                break;
            }
            // For QoS 0 there is no packet identifier: the topic name is followed directly by the payload.
            $topic = decodeString($data, $offset);
            // Forward the original PUBLISH packet verbatim to every connection subscribed to the topic
            // (including the publishing connection itself, if subscribed - just like a real MQTT broker).
            foreach (array_keys($subscriptions[$topic] ?? []) as $subscriber) {
                $server->send($subscriber, $data);
            }
            break;
        case MQTT_PINGREQ:
            $server->send($fd, "\xd0\x00");
            break;
        case MQTT_DISCONNECT:
            $server->close($fd);
            break;
    }
});

$server->on('close', function (Server $server, int $fd) use (&$subscriptions): void {
    // Drop the closed connection from the subscription table.
    foreach ($subscriptions as $topic => $subscribers) {
        unset($subscriptions[$topic][$fd]);
        if ($subscriptions[$topic] === []) {
            unset($subscriptions[$topic]);
        }
    }
});

// The MQTT clients, in a separate process: a subscriber and a publisher, using the Mosquitto command-line clients.
$clients = new Process(
    function () use ($server): void {
        $options = ['-h', '127.0.0.1', '-p', (string) $server->port, '-t', 'test/topic'];

        // Subscribe to the topic in the background, and wait (up to 5 seconds) for one message.
        $subscriber = proc_open(['mosquitto_sub', ...$options, '-C', '1', '-W', '5'], [1 => ['pipe', 'w']], $pipes);
        if ($subscriber === false) {
            echo 'Failed to start mosquitto_sub.', PHP_EOL;
            $server->shutdown();
            return;
        }

        // Publish the message, repeatedly until the subscriber has received it: the subscriber needs a moment to
        // connect and subscribe, and messages published before that have no subscriber and are dropped (QoS 0).
        for ($i = 0; $i < 50 && proc_get_status($subscriber)['running']; $i++) {
            exec('mosquitto_pub ' . implode(' ', array_map('escapeshellarg', [...$options, '-m', 'Hello, MQTT'])));
            usleep(100_000);
        }
        echo 'The subscriber received: ', trim((string) stream_get_contents($pipes[1])), PHP_EOL;
        proc_close($subscriber);

        $server->shutdown();
    }
);
$server->addProcess($clients);

$server->start();
