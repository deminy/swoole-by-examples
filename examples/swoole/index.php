#!/usr/bin/env php
<?php

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$http = new Server("0.0.0.0", 9501);
$http->on(
    "request",
    function (Request $req, Response $res) {
        $res->end("Hello, World!");
    }
);
$http->start();
