#!/usr/bin/env php
<?php

/**
 * In this example, ten HTTP/1 requests are made using the curl extension.
 * Each request takes about two seconds to finish; however, since the requests are made in non-blocking mode, it takes
 * barely over two seconds to finish all the requests.
 *
 * How to run this script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "./hooks/curl.php"
 *
 * You can run following command to see how much time it takes to run the script:
 *     docker exec -t $(docker ps -qf "name=client") bash -c "time ./hooks/curl.php"
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;

Coroutine::set([Constant::OPTION_HOOK_FLAGS => SWOOLE_HOOK_CURL]);

co\run(function () {
    for ($i = 0; $i < 10; $i++) {
        go(function () {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://server:9501?sleep=2");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($statusCode !== 234) {
                throw new Exception("Status code returned from the built-in HTTP/1 server should be 234.");
            }
        });
    }
});
