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
docker compose exec -ti server bash # Get a Bash shell in the server container.
docker compose exec -ti client bash # Get a Bash shell in the client container.
```

## List of Examples

* CSP programming
    * from blocking I/O to non-blocking I/O
        * The blocking version can be found [here](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/blocking-io.php).
        * The non-blocking version of the same script can be found [here](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io.php). You can also check [this script](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io-debug.php) to see how the non-blocking version is executed in order.
        * [This example](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/blocking-vs-non-blocking.php) shows how the _return_ statement is treated differently in Swoole. As you can see in the example, a function call could return a value back first before finishing its execution.
    * coroutines
        * create coroutines
            * [use different types of callbacks when creating coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/creation-2.php)
            * [use different functions/methods to create coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/creation-1.php)
        * enable coroutines
        * [coroutines in a for loop](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/for.php)
        * [nested coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/nested.php)
        * [exit from coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/exit.php)
        * [yield and resume coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/yield-and-resume.php)
        * [context](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/context.php): How to use the [Context](https://github.com/swoole/ide-helper/blob/master/src/swoole/Swoole/Coroutine/Context.php) objects in coroutines.
        * [benchmark](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/benchmark.php): In this example we create 1,000,000 coroutines in a single process; each coroutine sleeps for 5 seconds.
    * channels
        * [basic usage](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/channel.php)
        * [class \Swoole\Coroutine\WaitGroup](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/waitgroup.php) (like [the WaitGroup type in Golang](https://golang.org/pkg/sync/#WaitGroup))
    * [defer](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/defer.php)
    * runtime hooks
        * [configure and utilize different runtime hook flags in Swoole](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/hook-flags.php)
        * curl. There are two different ways to hook curl functions:
            * [Option SWOOLE_HOOK_NATIVE_CURL](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/native-curl.php) (recommended)
            * [Option SWOOLE_HOOK_CURL](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/curl.php): This approach is implemented through [Swoole Library](https://github.com/swoole/library); however, it doesn't work for _curl_multi_*_ functions.
        * The `mysqli` extension.
            * [hook _mysqli_ functions](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/mysqli.php)
        * The `PDO` (PHP Data Objects) extension.
            * [hook _PDO MySQL_ functionsaccess MySQL databases](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo_mysql.php)
            * [hook _PDO_PGSQL_ functions to access PostgreSQL databases](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo_pgsql.php)
            * [hook _PDO_SQLITE_ functions to access SQLite 3 databases](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo_sqlite.php)
    * deadlocks
        * examples on deadlocks
            * [pop data from an empty channel](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/an-empty-channel.php)
            * [push data to a full channel](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/channel-is-full.php)
            * [try to lock a locked file while the existing lock never gets released](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/file-locking.php)
            * [acquire a locked lock from another coroutine](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/swoole-lock.php)
            * [improperly shutdown or reload a server](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/server-shutdown.php)
            * When the only coroutine yields its execution. The examples are shown in the next section when we talk about `How to detect/handle deadlocks`.
        * How to detect/handle deadlocks. In the following examples, we trigger deadlocks by yielding the execution of the only coroutine in the program.
            * [show deadlock information (the default behavior)](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/coroutine-yielded-1.php)
            * [hide deadlock information](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/coroutine-yielded-2.php)
            * [set a customized exit condition](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/coroutine-yielded-3.php)
    * advanced topics
        * CPU-intensive job scheduling
            1. [non-preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/non-preemptive.php)
            2. [preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/preemptive.php)
            3. [mixed scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/mixed.php)
        * [class \Swoole\Coroutine\Barrier](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/barrier.php)
        * block coroutines/processes
            * [block a coroutine](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-coroutine.php)
            * [block a process using class \Swoole\Lock](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-process-using-swoole-lock.php)
            * [block processes using class \Swoole\Lock](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-lock.php)
            * [block processes using class \Swoole\Atomic](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-atomic.php)
        * unit tests
* server-side programming
    * application servers
        * [HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1.php): support gzip compression, serving static content, customizing status code, etc.
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
    * cronjobs
    * benchmark
        * base mode vs multi-process mode
    * advanced topics
        * [Rock Paper Scissors](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/rock-paper-scissors.php): implement the hand game Rock Paper Scissors using Swoole.
        * [How are different server events triggered?](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/server-events.php)
        * Server Combo (two different implementations)
            * [integrated HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1-integrated.php): an HTTP/1 server that supports cron jobs and synchronous/asynchronous tasks.
            * [integrated WebSocket server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/websocket-integrated.php): a WebSocket server that supports cron jobs and asynchronous tasks. This implementation use separate processes to handle cron jobs and task queues.
        * mixed protocols
            * [support HTTP/1, HTTP/2, and WebSocket on same port](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/mixed-protocols-1.php)
            * support multiple protocols on same server
        * [DDoS protection](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/ddos-protection.php): How to protect your Swoole-based application server from DDoS attacks.
        * [interruptible sleep](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/interruptible-sleep.php): This example shows how to set up a cronjob in a web server, and allow the cronjob to execute at a last time when the server is shutting down.
        * multiple ports listening
* client-side programming
    * [HTTP/1 client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/http1.php)
    * HTTP/2 client
    * [WebSocket client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/websocket.php)
    * [TCP client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/tcp.php)
    * [UDP client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/udp.php)
* miscellaneous topics
    * data management in Swoole: globals, persistence, and caching
        * [APCu caching]: APCu caching in Swoole works the same way as in other PHP CLI applications. This example explains it in details.
    * atomic counters
        * [implement atomic counters using unsigned 32-bit integers](https://github.com/deminy/swoole-by-examples/blob/master/examples/misc/atomic-counter-unsigned-32-bit.php)
        * [implement atomic counters using signed 64-bit integers](https://github.com/deminy/swoole-by-examples/blob/master/examples/misc/atomic-counter-signed-64-bit.php)
    * multiprocessing
        * wait and wakeup processes
        * process pool
            * pool creation and inter-process communication: Please check previous section `resource pooling` for details.
            * detach processes from a process pool

[APCu Caching]: https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/apcu-caching.php
