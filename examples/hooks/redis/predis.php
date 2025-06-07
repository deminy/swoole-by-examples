#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we test concurrent slow Redis operations using predis.
 *
 * We make five Redis connections to a Redis server; each Redis connection (client) takes about three seconds to finish.
 * In non-blocking mode, it takes about 15 seconds to complete all the operations across those five Redis connections
 * (clients). However, since operations in different Redis connections (clients) are executed concurrently in Swoole, it
 * takes barely over three seconds to complete all the operations across all Redis connections (clients).
 *
 * Before running this script, you need to install predis first using the following command:
 *     docker compose exec -t client bash -c "composer global require predis/predis=~3.0"
 *
 * Now you can use following command to run this script:
 *     docker compose exec -t client bash -c "./hooks/redis/predis.php"
 *
 * You can also run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/redis/predis.php"
 */

require_once "{$_ENV['HOME']}/.composer/vendor/autoload.php"; // @phpstan-ignore encapsedStringPart.nonString

use Predis\Client;
use Swoole\Constant;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// This statement is optional because hook flags are set to SWOOLE_HOOK_ALL by default, and flag SWOOLE_HOOK_ALL has
// flag SWOOLE_HOOK_TCP included already.
Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_TCP]);

run(function (): void {
    for ($i = 0; $i < 5; $i++) {
        go(function (): void {
            $client = new Client(['scheme' => 'tcp', 'host' => 'redis', 'port' => 6379]);

            // Use unique keys for each coroutine to avoid conflicts.
            $key = uniqid(sprintf('test-%d-', Coroutine::getCid())); // @phpstan-ignore argument.type
            $client->set($key, 'dummy', 'EX', 10); // Set a key with an expiration time of 10 seconds.
            assert($client->get($key) === 'dummy', 'The value stored in Redis should be "dummy".');

            // This will block the coroutine for 3 seconds since the list does not exist.
            $client->blpop('non-existing-list', 3.0);

            $client->disconnect();
        });
    }
});

echo PHP_EOL;
