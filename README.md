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

The examples are grouped by topic, progressing from coroutine fundamentals to servers, clients, and operational
patterns like multiprocessing and cronjobs.

* CSP programming: coroutine fundamentals — how Swoole turns blocking PHP code into concurrent, non-blocking code.
    * from blocking I/O to non-blocking I/O
        * [blocking I/O](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/blocking-io.php): a plain PHP script in which every call blocks the whole process.
        * [non-blocking I/O](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io.php): the same script rewritten with coroutines. There is also [a debug version](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/non-blocking-io-debug.php) showing the exact order in which the non-blocking version executes.
        * [blocking vs non-blocking](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/blocking-vs-non-blocking.php): how the _return_ statement is treated differently in Swoole — a function call could return a value back first before finishing its execution.
    * coroutines
        * create coroutines
            * [use different functions/methods to create coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/creation-syntax-variants.php)
            * [use different types of callbacks when creating coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/creation-callback-types.php)
        * [coroutines in a for loop](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/for.php)
        * [nested coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/nested.php)
        * [yield and resume coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/yield-and-resume.php)
        * [exit from coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/exit.php)
        * [enable and disable coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/enable-and-disable.php): the different ways to enable and disable coroutine support (runtime hooks) in a standalone process.
        * [context](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/context.php): per-coroutine data storage using [Context](https://github.com/swoole/ide-helper/blob/master/src/swoole/Swoole/Coroutine/Context.php) objects.
        * [benchmark](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/coroutines/benchmark.php): create 1,000,000 coroutines in a single process; each coroutine sleeps for 5 seconds.
    * channels and synchronization
        * [channels: basic usage](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/channel.php): pass data between coroutines.
        * [class \Swoole\Coroutine\WaitGroup](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/waitgroup.php): wait for a group of coroutines to finish (like [the WaitGroup type in Golang](https://golang.org/pkg/sync/#WaitGroup)).
        * [class \Swoole\Coroutine\Barrier](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/barrier.php): wait for a set of coroutines to finish by tracking references to a shared barrier object.
        * [defer](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/defer.php): register cleanup callbacks that run (in reverse order) when a coroutine finishes.
    * runtime hooks: make blocking PHP functions and extensions coroutine-friendly without changing their code.
        * [configure and utilize different runtime hook flags in Swoole](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/hook-flags.php)
        * curl. There are two different ways to hook curl functions:
            * [Option SWOOLE_HOOK_NATIVE_CURL](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/native-curl.php) (recommended)
            * [Option SWOOLE_HOOK_CURL](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/curl.php): This approach is implemented through [Swoole Library](https://github.com/swoole/library); however, it doesn't work for _curl_multi_*_ functions.
        * the `mysqli` extension
            * [hook _mysqli_ functions](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/mysqli.php)
        * the `PDO` (PHP Data Objects) extension
            * [hook _PDO_MYSQL_ functions to access MySQL databases](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo_mysql.php)
            * [hook _PDO_PGSQL_ functions to access PostgreSQL databases](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo_pgsql.php)
            * [hook _PDO_SQLITE_ functions to access SQLite 3 databases](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/pdo_sqlite.php)
        * Redis clients
            * [concurrent connections/operations using phpredis](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/redis/phpredis.php)
            * [concurrent connections/operations using predis](https://github.com/deminy/swoole-by-examples/blob/master/examples/hooks/redis/predis.php)
    * locks: the same locking concept at three different scopes.
        * [use a lock across coroutines](https://github.com/deminy/swoole-by-examples/blob/master/examples/locks/lock-across-coroutines.php) (Swoole v6.1.0+ only)
        * [use a lock across processes](https://github.com/deminy/swoole-by-examples/blob/master/examples/locks/lock-across-processes.php)
        * [use a lock across threads](https://github.com/deminy/swoole-by-examples/blob/master/examples/locks/lock-across-threads.php) (Swoole v6.1.0+ only)
    * deadlocks
        * how deadlocks happen
            * [pop data from an empty channel](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/an-empty-channel.php)
            * [push data to a full channel](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/channel-is-full.php)
            * [try to lock a locked file while the existing lock never gets released](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/file-locking.php)
            * [acquire a locked lock from another coroutine](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/swoole-lock.php)
            * [improperly shutdown or reload a server](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/server-shutdown.php)
            * When the only coroutine yields its execution. The examples are shown in the next section when we talk about `How to detect/handle deadlocks`.
        * how to detect/handle deadlocks. In the following examples, we trigger deadlocks by yielding the execution of the only coroutine in the program.
            * [show deadlock information (the default behavior)](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/coroutine-yielded-default-behavior.php)
            * [hide deadlock information](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/coroutine-yielded-deadlock-check-disabled.php)
            * [set a customized exit condition](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/deadlocks/coroutine-yielded-custom-exit-condition.php)
    * advanced topics
        * CPU-intensive job scheduling: how coroutines behave when nothing yields voluntarily.
            1. [non-preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/non-preemptive.php)
            2. [preemptive scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/preemptive.php)
            3. [mixed scheduling](https://github.com/deminy/swoole-by-examples/blob/master/examples/csp/scheduling/mixed.php)
        * block coroutines/processes: what "blocking" really blocks — a single coroutine, or the whole process.
            * [block a coroutine](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-coroutine.php)
            * [block a process using class \Swoole\Lock](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-a-process-using-swoole-lock.php)
            * [block processes using class \Swoole\Lock](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-lock.php)
            * [block processes using class \Swoole\Atomic](https://github.com/deminy/swoole-by-examples/blob/master/examples/io/block-processes-using-swoole-atomic.php)
        * unit tests
* server-side programming
    * application servers: one server per protocol.
        * [HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1.php): support gzip compression, serving static content, customizing status code, etc.
        * [HTTP/2 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http2.php)
        * [HTTP/1 SSE server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1-sse.php): stream responses chunk by chunk over HTTP/1.1 using Server-Sent Events, the mechanism behind ChatGPT-style text streaming.
            * HTTP/2 server push
        * [WebSocket server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/websocket.php)
        * TCP server
            * [event-driven style](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp1.php)
            * [coroutine style](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/tcp2.php)
        * [UDP server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/udp.php)
        * [Redis server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/redis.php): a server speaking the Redis protocol, usable from any Redis client.
        * [MQTT broker](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/mqtt.php): a minimal MQTT broker built on the _open_mqtt_protocol_ setting, supporting a basic publish/subscribe round trip with the Mosquitto command-line clients.
        * [reverse-proxy server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/proxy.php): a TCP-level reverse proxy relaying each connection to an upstream server.
        * [multiple ports listening](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/multiple-ports.php): one server listening on multiple ports, each port with its own set of callbacks.
    * integrated servers: multiple protocols/features combined in a single server.
        * [integrated HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1-integrated.php): an HTTP/1 server that supports cron jobs and synchronous/asynchronous tasks.
        * [integrated WebSocket server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/websocket-integrated.php): a WebSocket server that supports cron jobs and asynchronous tasks, using separate processes to handle cron jobs and task queues.
        * mixed protocols
            * [support HTTP/1, HTTP/2, and WebSocket on same port](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/mixed-protocols-same-port.php)
            * [support multiple protocols on different ports of one server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/mixed-protocols-per-port.php)
    * server lifecycle and reliability
        * [How are different server events triggered?](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/server-events.php)
        * [enable and disable coroutines in a server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/enable-coroutine.php)
        * [interruptible sleep](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/interruptible-sleep.php): let a cronjob inside a web server execute one last time when the server is shutting down.
        * network connection detection (dead network detection)
            * [heartbeat](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/heartbeat.php)
            * [TCP keepalive](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/keepalive.php)
        * [DDoS protection](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/ddos-protection.php): How to protect your Swoole-based application server from DDoS attacks.
    * resource pooling
        * process pool: reusable worker processes managed by class _\Swoole\Process\Pool_, with different IPC options.
            * [standalone](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-standalone.php)
            * [using message queue](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-msgqueue.php)
            * [using TCP socket](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-tcp-socket.php)
            * [using Unix socket](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/pool-unix-socket.php)
        * connection pool: share a bounded set of database/Redis connections among coroutines.
            * [MySQL connection pool](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/database-pool/mysqli.php)
            * [PostgreSQL connection pool](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/database-pool/pdo_pgsql.php)
            * [Redis connection pool](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/database-pool/redis.php)
            * How to implement a customized connection pool? Check package [crowdstar/vertica-swoole-adapter](https://github.com/Crowdstar/vertica-swoole-adapter) for details. This package implements connection pool for HP Vertica databases through ODBC, and it's maintained by me.
    * task scheduling and handling
        * [timer](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/timer.php): recurring and one-off timers via class _\Swoole\Timer_.
            * There is [a 2nd example](https://github.com/deminy/swoole-by-examples/blob/master/examples/timer/coroutine-style.php) included to show how to implement timer using coroutines only.
            * To see how to setup cronjobs using the _\Swoole\Timer_ class in an application server, please check [integrated HTTP/1 server](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/http1-integrated.php). For the full spectrum of recurring-job patterns, see the `cronjobs` section below.
    * benchmark
        * base mode vs multi-process mode
    * advanced topics
        * [Rock Paper Scissors](https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/rock-paper-scissors.php): implement the hand game Rock Paper Scissors using Swoole — one server coordinating three concurrent players.
* multiprocessing
    * [wait and wakeup processes](https://github.com/deminy/swoole-by-examples/blob/master/examples/misc/wait-and-wakeup-processes.php): block one process and wake it up from another, using shared-memory class _\Swoole\Atomic_.
    * process pool
        * pool creation and inter-process communication: Please check previous section `resource pooling` for details.
        * [detach processes from a process pool](https://github.com/deminy/swoole-by-examples/blob/master/examples/pool/process-pool/detach.php): let a worker escape the pool manager's control to finish a long task at its own pace.
* cronjobs: recurring in-process jobs, implemented in six different ways.
    * standalone cronjobs: the scheduler is a program of its own, deployed and supervised independently.
        * [using timers](https://github.com/deminy/swoole-by-examples/blob/master/examples/cronjobs/timer-tick.php): fixed-rate scheduling via _\Swoole\Timer_.
        * [using a coroutine loop](https://github.com/deminy/swoole-by-examples/blob/master/examples/cronjobs/coroutine-sleep.php): overlap-free scheduling with a plain coroutine sleep.
        * [using an interruptible sleep](https://github.com/deminy/swoole-by-examples/blob/master/examples/cronjobs/interruptible-channel.php): a Channel-based sleep that a shutdown can wake instantly.
        * [using a standalone process pool](https://github.com/deminy/swoole-by-examples/blob/master/examples/cronjobs/process-pool.php): cron as its own supervised deployable, with the schedule sharded across pool workers.
    * cronjobs as part of a server: the scheduler runs inside an application server, following the server's lifecycle and sharing its state.
        * [using a worker-registered timer plus task workers](https://github.com/deminy/swoole-by-examples/blob/master/examples/cronjobs/tick-to-task.php): a _\Swoole\Timer_ guarded to one server worker dispatches scheduled work to blocking-safe task workers.
        * [using a dedicated user process](https://github.com/deminy/swoole-by-examples/blob/master/examples/cronjobs/user-process.php): an isolated scheduler process attached to a server, with SIGTERM-driven graceful shutdown.
* event listening and handling
    * [the default exit condition](https://github.com/deminy/swoole-by-examples/blob/master/examples/events/default-exit-condition.php): signal listeners alone do not keep a process running - by default, the process exits before any signal can be handled.
    * [a customized exit condition](https://github.com/deminy/swoole-by-examples/blob/master/examples/events/customized-exit-condition.php): option _exit_condition_ keeps the event loop alive while signal listeners are registered, so a process can stay running just to listen for signals.
    * [wait for signals inside a coroutine](https://github.com/deminy/swoole-by-examples/blob/master/examples/events/wait-signal.php): method _\Swoole\Coroutine\System::waitSignal()_ blocks the calling coroutine until a signal arrives or a timeout expires - no callbacks involved.
* built-in clients provided by Swoole
    * [HTTP/1 client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/http1.php)
    * HTTP/2 client
    * [WebSocket client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/websocket.php)
    * [TCP client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/tcp.php)
    * [UDP client](https://github.com/deminy/swoole-by-examples/blob/master/examples/clients/udp.php)
* miscellaneous topics
    * data management in Swoole: globals, persistence, and caching
        * [APCu caching]: APCu caching in Swoole works the same way as in other PHP CLI applications. This example explains it in details.
    * atomic counters: shared-memory counters that work across processes.
        * [implement atomic counters using unsigned 32-bit integers](https://github.com/deminy/swoole-by-examples/blob/master/examples/misc/atomic-counter-unsigned-32-bit.php)
        * [implement atomic counters using signed 64-bit integers](https://github.com/deminy/swoole-by-examples/blob/master/examples/misc/atomic-counter-signed-64-bit.php)
    * [UDP multicast](https://github.com/deminy/swoole-by-examples/blob/master/examples/misc/multicast.php): make a UDP server join an IP multicast group and receive datagrams sent to the group address.

[APCu Caching]: https://github.com/deminy/swoole-by-examples/blob/master/examples/servers/apcu-caching.php
