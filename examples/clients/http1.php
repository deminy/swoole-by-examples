#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example two HTTP/1 requests are made in non-blocking mode. Since the 2nd request usually takes
 * much less time to finish, you should see following output in most cases:
 *     Done executing the second HTTP/1 request.
 *     Done executing the first HTTP/1 request.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./clients/http1.php"
 */

use Swoole\Coroutine\Http\Client;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    // For the 1st one, we make an HTTPS request to download an image file from GitHub. Once this script
    // finishes execution, you should see an image file "mascot.png" under same directory of this script.
    go(function (): void {
        $client = new Client('raw.githubusercontent.com', 443, true);
        $client->set(
            [
                'timeout' => -1,
            ]
        );
        $client->setHeaders(
            [
                'Accept-Encoding' => 'gzip',
            ]
        );
        $client->download('/swoole/swoole-src/master/mascot.png', __DIR__ . '/mascot.png');

        echo 'Done executing the first HTTP/1 request.', PHP_EOL;
    });

    // For the 2nd one, we make an HTTP request to the HTTP/1 server started in the "server" container.
    go(function (): void {
        $client = new Client('server', 9501);
        $client->get('/');

        // You can uncomment following code to print out the HTTP response headers and body.
        // foreach ($client->getHeaders() as $key => $value) {
        //    echo "{$key}: $value", PHP_EOL;
        // }
        // echo PHP_EOL, $client->body;

        echo 'Done executing the second HTTP/1 request.', PHP_EOL;
    });
});
