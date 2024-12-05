#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * In this example, we implement the classic hand game Rock Paper Scissors.
 *
 * Assuming there are three players A, B, and C, they choose their shapes separately by making a selection on the web
 * form and submitting their choices through following pages. Once everyone has their choices submitted, there will be
 * three HTTP POST requests made to the server.
 *
 *   * http://127.0.0.1:9801?name=A
 *   * http://127.0.0.1:9801?name=B
 *   * http://127.0.0.1:9801?name=C
 *
 * What happens next on the server side?
 *   1. The first two HTTP POST requests won't get responses immediately.
 *   2. Once all three HTTP requests are processed, they get responses simultaneously.
 *   3. All the responses are sent by the server when processing the third quest (the last request).
 *
 * For backend developers, instead of trying it using a web browser, you can execute following CLI commands in different
 * terminals and check the outputs:
 *   docker compose exec -ti client curl -d "shape=Rock"     "http://server:9801?name=A"
 *   docker compose exec -ti client curl -d "shape=Paper"    "http://server:9801?name=B"
 *   docker compose exec -ti client curl -d "shape=Scissors" "http://server:9801?name=C"
 */

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$form = <<<'EOT'
    <form method="post" action="">
        <input type="radio" value="Rock"     name="shape">Rock
        <input type="radio" value="Paper"    name="shape">Paper
        <input type="radio" value="Scissors" name="shape">Scissors
        <button type="submit">Submit</button>
    </form>
EOT;
$exchanges = [];

$server = new Server('0.0.0.0', 9801, SWOOLE_BASE);

$server->on(
    'request',
    function (Request $request, Response $response) use ($form, &$exchanges): void {
        $response->header('Content-Type', 'text/html; charset=UTF-8');
        if ($request->server['request_method'] == 'GET') {
            $response->end($form); // Show the web form.
        } else {
            $exchanges[$request->get['name']] = [$request, $response];

            if (count($exchanges) == 3) {
                $result = '';
                foreach ($exchanges as [$request]) {
                    $result .= "{$request->get['name']}: {$request->post['shape']}; ";
                }
                $result = substr($result, 0, -2) . '.' . PHP_EOL;

                foreach ($exchanges as [, $response]) { // Send responses back.
                    $response->end($result);
                }

                $exchanges = [];
            }
        }
    }
);

$server->start();
