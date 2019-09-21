# Swoole by Examples

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/deminy/swoole-by-examples/blob/master/LICENSE.txt)

The repository is to help developers to get familiar with [Swoole](https://github.com/swoole/swoole-src) through a variety of examples.

NOTE: I'm adding new examples for latest versions of Swoole, so please be patient.

## Setup the Development Environment

We use [the official Docker image of Swoole](https://hub.docker.com/r/phpswoole/swoole) to run the examples. There are
tens of examples under repository [swoole/docker-swoole](https://github.com/swoole/docker-swoole) shown how to use the
image. Please spend some time checking it first.

Before running the examples, please run command `docker-compose up -d` under the root repository directory to start a
Docker container.

## List of Examples

* CSP programming
    * from blocking I/O to non-blocking I/O
        * The blocking version can be found [here](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/blocking-io.php).
        * The non-blocking version of the same script can be found [here](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io.php). You can also check [this script](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io-debug.php) to see how the non-blocking version is executed in order.
    * coroutines
        * enable coroutines
        * [coroutines in a for loop](https://github.com/deminy/swoole-by-examples/blob/master/examples/for.php)
        * nested coroutines
    * channels
        * waitGroup
    * [defer](https://github.com/deminy/swoole-by-examples/blob/master/examples/defer.php)
* server-side programming
    * application servers
        * HTTP/1 server
        * HTTP/2 server
        * WebSocket server
        * TCP server
        * UDP server
    * resource pooling
        * process pool
        * connection pool
    * port listening
        * multiple protocols on the same port
        * multiple ports listening
    * network connection detection
        * dead network detection
        * heartbeats
    * task scheduling and handling
        * [timer](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer.php). There is [a 2nd example](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer-in-coroutine-style.php) included to show how to implement timer using coroutines only.
    * benchmark
        * single-process mode vs multi-process mode
* client-side programming
