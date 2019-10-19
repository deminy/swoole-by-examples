# Swoole by Examples

[![Build Status](https://travis-ci.com/deminy/swoole-by-examples.svg?branch=master)](https://travis-ci.com/deminy/swoole-by-examples)
[![License: CC BY-NC-ND 4.0](https://img.shields.io/badge/License-CC%20BY--NC--ND%204.0-lightgrey.svg)](https://creativecommons.org/licenses/by-nc-nd/4.0/)

The repository is to help developers to get familiar with [Swoole](https://github.com/swoole/swoole-src) through a variety of examples.

NOTE: I'm adding examples for latest versions of Swoole, so please be patient.

## Setup the Development Environment

We use Docker to setup our development environment. Other than Docker, you don't need to install any other software to
run and test the examples: you don't need to have PHP, Swoole, Composer, or some other software installed locally.

We use [the official Docker image of Swoole](https://hub.docker.com/r/phpswoole/swoole) to run the examples. There are
tens of examples under repository [swoole/docker-swoole](https://github.com/swoole/docker-swoole) shown how to use the
image. Please spend some time checking it first.

Before running the examples, please run command `docker-compose up -d` under the root repository directory to start the
Docker containers. There are two containers used to run the examples:

* a server container where application servers are running.
* a client container where client-side scripts should be executed.

Both containers have the same PHP scripts in place, so most standalone scripts (e.g., most CSP programming examples) can be
executed from either container.

## List of Examples

* CSP programming
    * from blocking I/O to non-blocking I/O
        * The blocking version can be found [here](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/blocking-io.php).
        * The non-blocking version of the same script can be found [here](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io.php). You can also check [this script](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io-debug.php) to see how the non-blocking version is executed in order.
    * coroutines
        * enable coroutines
        * [create coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/creation.php)
        * [yield and resume coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/yield-and-resume.php)
        * [coroutines in a for loop](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/for.php)
        * nested coroutines
        * [exit from coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/exit.php)
    * channels
        * [waitGroup](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/waitgroup.php) (like [the WaitGroup type in Golang](https://golang.org/pkg/sync/#WaitGroup))
    * [defer](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/defer.php)
    * advanced topics
        * CPU-intensive job scheduling
            1. [non-preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/non-preemptive.php)
            2. [preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/preemptive.php)
            3. [mixed scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/mixed.php)
* server-side programming
    * application servers
        * [HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1.php)
        * [HTTP/2 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http2.php)
            * HTTP/2 server push
        * [WebSocket server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/websocket.php)
        * [Redis server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/redis.php)
        * [TCP server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp.php)
        * UDP server
    * resource pooling
        * process pool
        * connection pool
    * network connection detection (dead network detection)
        * [heartbeat](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/heartbeat.php)
        * TCP keepalive
    * task scheduling and handling
        * [timer](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/timer.php). There is [a 2nd example](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/coroutine-style.php) included to show how to implement timer using coroutines only.
    * benchmark
        * single-process mode vs multi-process mode
    * advanced topics
        * mixed protocols
            * [support HTTP/1, HTTP/2, and WebSocket on same port](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/mixed-protocols-1.php)
            * support multiple protocols on same server
        * multiple ports listening
* client-side programming
    * [HTTP/1 client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/http1.php)
    * HTTP/2 client
    * WebSocket client
    * TCP client
* Swoole extensions
    * async
    * orm
    * postgresql
    * serialize
    * zookeeper
