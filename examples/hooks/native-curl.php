#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, six non-blocking HTTP/1 requests are made by enabling option "SWOOLE_HOOK_NATIVE_CURL" and using
 * curl_multi_* functions.
 * Each request takes about two seconds to finish; however, since the requests are made in non-blocking mode, it takes
 * barely over two seconds to finish all the requests.
 *
 * Notes:
 *     * This feature works under Swoole 4.6.0+.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./hooks/native-curl.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker compose exec -t client bash -c "time ./hooks/native-curl.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_NATIVE_CURL]);

co\run(function () {
    for ($i = 0; $i < 3; $i++) {
        go(function () {
            $mh = curl_multi_init();

            $handlers = [];
            for ($j = 0; $j < 2; $j++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'http://server:9501?sleep=2');
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                curl_multi_add_handle($mh, $ch);
                $handlers[] = $ch;
            }

            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh);
                }
            } while ($active && $status == CURLM_OK);

            foreach ($handlers as $ch) {
                $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                curl_multi_remove_handle($mh, $ch);
                if ($statusCode !== 234) {
                    throw new Exception('Status code returned from the built-in HTTP/1 server should be 234.');
                }
            }
            curl_multi_close($mh);
        });
    }
});
