#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, six non-blocking HTTP/1 requests are made by enabling hook flag SWOOLE_HOOK_CURL for the curl
 * extension.
 * Each request takes about two seconds to finish; however, since the requests are made in non-blocking mode, it takes
 * barely over two seconds to finish all the requests.
 *
 * Notes:
 *     * This approach doesn't work for curl_multi_* functions. To hook curl_multi_* functions, please check the other
 *       example in file "./hooks/native-curl.php".
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/curl.php"
 *
 * You can run the following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/curl.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

// SWOOLE_HOOK_CURL is not included in SWOOLE_HOOK_ALL (the default hook flags), which uses the newer
// SWOOLE_HOOK_NATIVE_CURL instead (see native-curl.php). The two flags are alternatives; this example enables the older
// SWOOLE_HOOK_CURL explicitly.
Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_CURL]);

run(function (): void {
    for ($i = 0; $i < 6; $i++) {
        go(function (): void {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://server:9501?sleep=2');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($statusCode !== 234) {
                throw new Exception('Status code returned from the built-in HTTP/1 server should be 234.');
            }
        });
    }
});
