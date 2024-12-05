#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a mini-version of Redis server, where only the Redis "get" and "set" commands are
 * implemented.
 *
 * This Redis server listens requests on port 6379, and you can connect to it just like connecting to any other Redis
 * server. You can use the Redis extension (phpredis), the popular predis library, or some other Redis clients to
 * talk to the Redis server.
 *
 * In this repository there are two client-side scripts included to talk to the server: one uses phpredis and the other
 * one uses predis. Here are the commands to run the two client-side scripts:
 *     docker compose exec -t client bash -c "./clients/redis/phpredis.php"
 *     docker compose exec -t client bash -c "./clients/redis/predis.php"
 * You can find the scripts from
 *     1. https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/redis/phpredis.php
 *     2. https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/redis/predis.php
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
