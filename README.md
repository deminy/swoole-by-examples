# Swoole by Examples

[![License: CC BY-NC-ND 4.0](https://img.shields.io/badge/License-CC%20BY--NC--ND%204.0-lightgrey.svg)](https://creativecommons.org/licenses/by-nc-nd/4.0/)

The repository is to help developers to get familiar with [Swoole](https://github.com/swoole/swoole-src) through a 
variety of examples. All the examples are fully functioning; they can be executed and verified using the Docker images
provided.

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
executed from either container. Once the containers are running, you can use one of following commands to get a Bash shell
in the containers:

```bash
docker exec -ti $(docker ps -qf "name=server") bash # Get a Bash shell in the server container.
docker exec -ti $(docker ps -qf "name=client") bash # Get a Bash shell in the client container.
```

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
        * [nested coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/nested.php)
        * [exit from coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/exit.php)
        * context
        * [benchmark](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/benchmark.php): In this example we create 1,000,000 coroutines in a single process; each coroutine sleeps for 5 seconds.
    * channels
        * [basic usage](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/channel.php)
        * [waitGroup](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/waitgroup.php) (like [the WaitGroup type in Golang](https://golang.org/pkg/sync/#WaitGroup))
    * [defer](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/defer.php)
    * advanced topics
        * CPU-intensive job scheduling
            1. [non-preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/non-preemptive.php)
            2. [preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/preemptive.php)
            3. [mixed scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/mixed.php)
        * runtime hooks
            * curl. There are two different ways to hook curl functions:
                * [Option SWOOLE_HOOK_CURL](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/curl.php): This approach is implemented through [Swoole Library](https://github.com/swoole/library); however, it doesn't work for _curl_multi_*_ functions.
                * [Option SWOOLE_HOOK_NATIVE_CURL](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/native-curl.php)
            * [hook _MySQL_/_PDO_ functions](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo.php)
        * unit tests
* server-side programming
    * application servers
        * [HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1.php): support gzip compression, serving static content, customizing status code, etc.
            * [integrated HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1-integrated.php): an HTTP/1 server that supports cronjobs and synchronous/asynchronous tasks.
        * [HTTP/2 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http2.php)
            * HTTP/2 server push
        * [WebSocket server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/websocket.php)
        * [Redis server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/redis.php)
        * proxy server
        * TCP server
            * [event-driven style](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp1.php)
            * [coroutine style](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp2.php)
        * [UDP server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/udp.php)
    * resource pooling
        * process pool
            * [standalone](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-standalone.php)
            * [using message queue](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-msgqueue.php)
            * [using TCP socket](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-tcp-socket.php)
            * [using Unix socket](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-unix-socket.php)
        * connection pool
            * [Redis connection pool](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/database-pool/redis.php)
            * How to implement a customized connection pool? Check package [crowdstar/vertica-swoole-adapter](https://github.com/Crowdstar/vertica-swoole-adapter) for details. This package implements connection pool for HP Vertica databases through ODBC, and it's maintained by me.
    * network connection detection (dead network detection)
        * [heartbeat](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/heartbeat.php)
        * [TCP keepalive](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/keepalive.php)
    * task scheduling and handling
        * [timer](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/timer.php)
            * There is [a 2nd example](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/coroutine-style.php) included to show how to implement timer using coroutines only.
            * To see how to setup cronjobs using the _\Swoole\Timer_ class in an application server, please check [integrated HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1-integrated.php).
    * benchmark
        * single-process mode vs multi-process mode
    * advanced topics
        * mixed protocols
            * [support HTTP/1, HTTP/2, and WebSocket on same port](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/mixed-protocols-1.php)
            * support multiple protocols on same server
        * multiple ports listening
        * event listeners
* client-side programming
    * [HTTP/1 client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/http1.php)
    * HTTP/2 client
    * WebSocket client
    * [TCP client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/tcp.php)
* Swoole extensions
    * async
    * orm
    * postgresql
    * serialize
    * zookeeper
