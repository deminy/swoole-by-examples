#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we start a mini-version of Redis server, where only the Redis "get" and "set" commands are partially
 * implemented.
 *
 * Class \Swoole\Redis\Server speaks the Redis protocol, so any Redis client can talk to it: the Redis extension
 * (phpredis), the popular predis library, redis-cli, and so on. Each Redis command is handled by a callback registered
 * through method setHandler(), and the reply is built with method \Swoole\Redis\Server::format().
 *
 * To show that, the script starts the server, then uses the Redis extension (phpredis) to set and get a key, and to
 * get a key that doesn't exist, just as it would with a real Redis server. Since phpredis blocks while waiting for a
 * reply, the client runs in a separate process (added through method $server->addProcess()) rather than in the
 * server's own worker process. Once done, the script shuts the server down.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./servers/redis.php"
 */

use Swoole\Constant;
use Swoole\Process;
use Swoole\Redis\Server;
use Swoole\Table;

// On PHP 8.2+, method \Swoole\Redis\Server::setHandler() of Swoole 6.2 triggers "Creation of dynamic property is
// deprecated" notices, because it stores each handler as a dynamic property of the server object. They come from the
// extension, not from this script, so they are hidden here to keep the output clean.
error_reporting(E_ALL & ~E_DEPRECATED);

// We use a Swoole table as the data storage for the Redis server.
$table = new Table(1024);
$table->column('value', Table::TYPE_STRING, 64);
$table->create();

// Port 0 makes the server listen on a random unused port; the port picked is exposed as $server->port.
$server = new Server('127.0.0.1', 0);
$server->set(
    [
        Constant::OPTION_WORKER_NUM => 1,
    ]
);

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

// A Redis client in a separate process, talking to the server through the Redis extension (phpredis).
$client = new Process(
    function () use ($server): void {
        $redis = new Redis();
        $redis->connect('127.0.0.1', $server->port);
        echo 'SET foo bar: ', var_export($redis->set('foo', 'bar'), true), PHP_EOL;
        echo 'GET foo: ', var_export($redis->get('foo'), true), PHP_EOL;
        echo 'GET missing-key: ', var_export($redis->get('missing-key'), true), ' (the key does not exist)', PHP_EOL;
        $redis->close();

        $server->shutdown();
    }
);
$server->addProcess($client);

$server->start();
