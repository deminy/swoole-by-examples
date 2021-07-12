#!/usr/bin/env php
<?php

/**
 * In this example, we implement the classic hand game Rock Paper Scissors.
 *
 * Assuming there are three players A, B, and C, they choose their shapes separately by making a selection on the
 * web form and submitting their choices. Once everyone has their choices submitted, there will be three HTTP POST
 * requests made to the server, kind of like the following:
 *
 *   docker exec -t $(docker ps -qf "name=client") curl -d "shape=Rock"     "http://server:9801?name=A"
 *   docker exec -t $(docker ps -qf "name=client") curl -d "shape=Paper"    "http://server:9801?name=B"
 *   docker exec -t $(docker ps -qf "name=client") curl -d "shape=Scissors" "http://server:9801?name=C"
 *
 * What happens on the server side?
 *   1. The first two HTTP POST requests won't get responses immediately.
 *   2. Once all three HTTP requests were processed, they get responses simultaneously.
 *   3. All the responses are sent by the server when processing the third quest (the last request).
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$form = <<<EOT
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
    "request",
    function (Request $request, Response $response) use ($form, &$exchanges) {
        $response->header('Content-Type', 'text/html; charset=UTF-8');
        if ($request->server['request_method'] == 'GET') {
            $response->end($form); // Show the web form.
        } else {
            $exchanges[$request->get['name']] = [$request, $response];

            if (count($exchanges) == 3) {
                $result = '';
                foreach ($exchanges as list($request ,)) {
                    $result .= "{$request->get['name']}: {$request->post['shape']}; ";
                }
                $result = substr($result, 0, -2) . ".\n";

                foreach ($exchanges as list(, $response)) { // Send responses back.
                    $response->end($result);
                }

                $exchanges = [];
            }
        }
    }
);

$server->start();
