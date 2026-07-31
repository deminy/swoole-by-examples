#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a minimal MQTT broker on port 9514.
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
 * This script is managed by Supervisord inside the "server" container, thus there is no need to start it manually.
 *
 * How to verify the broker (using the Mosquitto command-line clients installed in the "client" container):
 * 1. In a first terminal, subscribe to a topic:
 *        docker compose exec -ti client mosquitto_sub -h server -p 9514 -t "test/topic"
 * 2. In a second terminal, publish a message to the same topic:
 *        docker compose exec -ti client mosquitto_pub -h server -p 9514 -t "test/topic" -m "Hello, MQTT"
 *    The first terminal now prints "Hello, MQTT".
 */

use Swoole\Constant;
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

$server = new Server('0.0.0.0', 9514);
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

$server->start();
