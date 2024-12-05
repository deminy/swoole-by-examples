#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example we create a Redis connection pool with maximally 11 connections (default pool size is 64).
 * We then repeatedly get a connection from the pool, execute a Redis set and Redis get command, and put back the
 * connection.
 *
 * The connection pool classes (\Swoole\Database\RedisConfig and \Swoole\Database\RedisPool in this example) are
 * built-in classes in Swoole since 4.4.13+.
 *
 * You can use following command to run this script:
 *     docker compose exec -t client bash -c "./pool/database-pool/redis.php"
 */

use Swoole\Coroutine\System;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    /** @var string $host */
    $host = System::gethostbyname('server');
    $pool = new RedisPool((new RedisConfig())->withHost($host), 11);
    for ($n = 1024; $n--;) {
        go(function () use ($pool): void {
            $redis  = $pool->get();
            $result = $redis->set('foo', 'bar');
            if (!$result) {
                throw new Exception('failed to set a value in Redis.');
            }
            $result = $redis->get('foo');
            if ($result != 'bar') {
                throw new Exception('failed to get data from Redis.');
            }
            $pool->put($redis);
        });
    }
});
