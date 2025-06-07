#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a mini-version of Redis server, where only the Redis "get" and "set" commands are
 * implemented.
 *
 * This Redis server listens requests on port 6379. We can connect to it just like connecting to any other Redis
 * server. We can use the Redis extension (phpredis), the popular predis library, or some other Redis clients to
 * talk to the Redis server.
 *
 * Here is an example using the Redis extension (phpredis) to connect to this server and set/get a key:
 * <code>
 * $client = new Redis();
 * $client->connect('server');
 * echo $client->set('foo', 'bar'), PHP_EOL;
 * echo $client->get('foo'), PHP_EOL;
 * </code>
 */

use Swoole\Redis\Server;
use Swoole\Table;

// We use a Swoole table as the data storage for the Redis server.
$table = new Table(1024);
$table->column('value', Table::TYPE_STRING, 64);
$table->create();

$server = new Server('0.0.0.0', 6379);

$server->setHandler('SET', function (int $fd, array $data) use ($server, $table): void {
    $table->set($data[0], ['value' => $data[1]]);
    $server->send($fd, Server::format(Server::STATUS, 'OK'));
});

$server->setHandler('GET', function (int $fd, array $data) use ($server, $table): void {
    $key = $data[0];
    if ($table->exist($key)) {
        $server->send($fd, Server::format(Server::STRING, $table->get($key)['value'])); // @phpstan-ignore offsetAccess.nonOffsetAccessible
    } else {
        $server->send($fd, Server::format(Server::NIL));
    }
});

$server->start();
