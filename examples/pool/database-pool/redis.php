#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we create a Redis connection pool with maximally 32 connections (default pool size is 64).
 * We then repeatedly get a connection from the pool, execute a Redis set and Redis get command, and put back the
 * connection.
 *
 * The connection pool classes (\Swoole\Database\RedisConfig and \Swoole\Database\RedisPool in this example) are
 * built-in classes in Swoole since 4.4.13+.
 *
 * You can use following command to run this script:
 *     docker compose exec -t client bash -c "./pool/database-pool/redis.php"
 */

use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $pool = new RedisPool((new RedisConfig())->withHost('redis'), 32);
    for ($n = 1024; $n--;) {
        go(function () use ($pool, $n): void {
            $redis = $pool->get();

            $key = uniqid(sprintf('test-%d-', $n)); // A unique key to avoid conflicts.
            $redis->set($key, 'dummy', ['EX' => 10]); // Set a key with an expiration time of 10 seconds.
            assert($redis->get($key) === 'dummy', 'The value stored in Redis should be "dummy".');

            $pool->put($redis);
        });
    }
});

echo 'Done', PHP_EOL;
