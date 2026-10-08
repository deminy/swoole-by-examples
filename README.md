# Swoole by Examples

[![Tests](https://github.com/deminy/swoole-by-examples/actions/workflows/tests.yml/badge.svg)](https://github.com/deminy/swoole-by-examples/actions/workflows/tests.yml)
[![License: CC BY-NC-ND 4.0](https://img.shields.io/badge/License-CC%20BY--NC--ND%204.0-lightgrey.svg)](https://creativecommons.org/licenses/by-nc-nd/4.0/)

[Swoole](https://github.com/swoole/swoole-src) is a PHP extension that turns PHP into a long-running, asynchronous
runtime, much like Node.js or Go. With PHP-FPM, every request starts from scratch, blocks on every database query or
HTTP call, and throws everything away when it ends. A Swoole application starts once, keeps objects and connections in
memory between requests, and runs thousands of concurrent tasks in a single process.

This repository teaches Swoole through small, runnable examples, one concept per script. Every example runs in the
provided Docker containers, and nearly every one is covered by an automated test that runs in CI.

## Table of contents

* [Supported versions](#supported-versions)
* [Quick start](#quick-start)
* [How to run the examples](#how-to-run-the-examples)
* [Key concepts in 60 seconds](#key-concepts-in-60-seconds)
* [Start here: why Swoole?](#start-here-why-swoole)
* [Coroutine basics](#coroutine-basics)
* [Coordinating coroutines](#coordinating-coroutines)
* [Runtime hooks](#runtime-hooks)
  * [curl](#curl)
  * [Databases](#databases)
  * [Redis](#redis)
  * [Connection pools](#connection-pools)
* [Coroutine clients](#coroutine-clients)
* [Servers](#servers)
  * [Your first server](#your-first-server)
  * [One server per protocol](#one-server-per-protocol)
  * [Multiple ports and protocols](#multiple-ports-and-protocols)
  * [Connection health and protection](#connection-health-and-protection)
  * [State inside a server](#state-inside-a-server)
* [Processes and shared memory](#processes-and-shared-memory)
  * [Process pools](#process-pools)
  * [Sharing data between processes](#sharing-data-between-processes)
  * [Synchronizing processes](#synchronizing-processes)
  * [Threads](#threads)
* [Signals and the event loop](#signals-and-the-event-loop)
* [Timers and cron jobs](#timers-and-cron-jobs)
  * [Standalone cron jobs](#standalone-cron-jobs)
  * [Cron jobs inside a server](#cron-jobs-inside-a-server)
* [Putting it all together](#putting-it-all-together)
* [Pitfalls and advanced topics](#pitfalls-and-advanced-topics)
  * [Deadlocks: how they happen](#deadlocks-how-they-happen)
  * [Deadlocks: detecting and handling them](#deadlocks-detecting-and-handling-them)
  * [CPU-bound code and scheduling](#cpu-bound-code-and-scheduling)
* [Testing Swoole code](#testing-swoole-code)
* [License](#license)

## Supported versions

All the examples are written for and tested on **PHP 8.4+** and **Swoole 6.2+**. The Docker images, the Composer
requirements, and the CI workflows all use these versions; the examples may not work on older versions of PHP or Swoole.
You don't need PHP, Swoole, or Composer installed locally: the only requirement is Docker with Compose v2 (the
`docker compose` command).

## Quick start

```bash
git clone https://github.com/deminy/swoole-by-examples.git
cd swoole-by-examples
docker compose up -d   # give it a few seconds for the servers to start

# Run a first example: 2,000 coroutines that each sleep for 1 second, all finishing in about 1 second.
docker compose exec -t client bash -c "time ./csp/coroutines/many-coroutines-in-a-loop.php"
```

## How to run the examples

`docker compose up -d` starts three PHP containers, plus the `redis`, `mysql`, and `postgresql` services that the
database and Redis examples connect to. The PHP containers mount this repository at `/var/www`, with `/var/www/examples`
as the working directory (which is why run commands use paths like `./csp/channel.php`):

| Container | What runs there | How to use it |
|---|---|---|
| `client` | Standalone scripts, and clients that talk to the servers | `docker compose exec -t client bash -c "./csp/channel.php"` |
| `server` | 16 long-running example servers (HTTP, WebSocket, TCP, MQTT, ...), started automatically by Supervisord and reloaded when their script changes | Nothing to start: connect to `server:<port>` from the `client` container |
| `zts` | The [thread examples](#threads), which need a thread-safe (ZTS) build of PHP | `docker compose exec -t zts php ./threads/map.php` |

* **Every example documents its exact run command in its docblock**, along with any extra steps. The "Run from" column
  in the tables below says where to run it: `client`, `server`, or "auto-started" for the servers that are already
  running.
* Run examples from the `client` container unless the docblock says otherwise. The two images differ: for example, only
  `client` has the `mysqli` extension, and only `server` has APCu and System V message queues.
* Server ports are not published to your host (except port 9801, for [Rock Paper Scissors](#putting-it-all-together)),
  so send requests from the `client` container, e.g. `docker compose exec -t client curl -i http://server:9501`.
* To follow the servers' output, run `docker compose logs -f server`. To open a shell, run
  `docker compose exec -ti client bash` (or `server`). To stop everything, run `docker compose down`.

The images are built from the [official Swoole image](https://hub.docker.com/r/phpswoole/swoole)
(`phpswoole/swoole:6.2-php8.4`) with a few extra extensions and tools; see [dockerfiles/](dockerfiles/). The repository
[swoole/docker-swoole](https://github.com/swoole/docker-swoole) has more examples of using that image.

## Key concepts in 60 seconds

* **Coroutine**: a lightweight function that can pause and resume. When a coroutine waits on I/O (a query, an HTTP call,
  a sleep), Swoole runs another coroutine instead of blocking the whole process. Thousands of them can run in a single
  process.
* **Event loop (scheduler)**: the part of Swoole that decides which coroutine runs next, and wakes coroutines up when
  their I/O is ready.
* **CSP and channels**: the concurrency style Swoole shares with Go (CSP stands for Communicating Sequential Processes).
  Coroutines pass data to each other through channels instead of sharing variables.
* **Runtime hooks**: the switch that makes ordinary blocking PHP code (`sleep()`, PDO, mysqli, curl, phpredis, file
  functions, ...) non-blocking inside coroutines, without changing the code.
* **Servers**: Swoole has built-in HTTP, WebSocket, TCP, UDP, and other servers, with no Nginx or PHP-FPM in front. A
  server runs a master process plus worker processes, and your code reacts to events such as "a request arrived".
* **Processes and shared memory**: for CPU-bound work or isolation, Swoole manages pools of worker processes, which
  share data through shared-memory structures (`Swoole\Table`, `Swoole\Atomic`) and locks.

## Start here: why Swoole?

The same work, done the PHP-FPM way and the Swoole way. Read these in order.

| Example | What it shows | Run from |
|---|---|---|
| [Blocking I/O](examples/io/blocking-io.php) | A plain PHP script whose two simulated I/O calls run one after the other, taking about 3 seconds | client |
| [Non-blocking I/O](examples/io/non-blocking-io.php) ([debug version](examples/io/non-blocking-io-debug.php)) | The same work in two coroutines that wait at the same time, taking about 2 seconds; the debug version prints the order in which they interleave | client |
| [Blocking vs non-blocking](examples/io/blocking-vs-non-blocking.php) | A function that starts a coroutine returns right away, while the coroutine it started keeps running | client |
| [1,000,000 coroutines](examples/csp/coroutines/benchmark.php) | How cheap coroutines are: start a million of them in one process, each sleeping for 5 seconds (needs about 8 GB of RAM) | client |

## Coroutine basics

| Example | What it shows | Run from |
|---|---|---|
| [Ways to create a coroutine](examples/csp/coroutines/creation-syntax-variants.php) | `go()`, `Swoole\Coroutine::create()`, `Co::create()`, and the other equivalent ways to start a coroutine | client |
| [Callback types](examples/csp/coroutines/creation-callback-types.php) | Any PHP callable (closure, function name, static or instance method) can be the body of a coroutine | client |
| [Many coroutines in a loop](examples/csp/coroutines/many-coroutines-in-a-loop.php) | 2,000 one-second sleeps finish in about 1 second instead of about 2,000 | client |
| [Nested coroutines](examples/csp/coroutines/nested.php) ([execution order](examples/csp/coroutines/nested-execution-order.php)) | Coroutines started inside other coroutines, and the order in which they run | client |
| [Yield and resume](examples/csp/coroutines/yield-and-resume.php) | Pause a coroutine by hand, and resume it later from another coroutine | client |
| [Cancel a coroutine](examples/csp/coroutines/cancel.php) | `Swoole\Coroutine::cancel()` interrupts a coroutine's sleep or channel wait from another coroutine, so it can stop early | client |
| [Block a coroutine](examples/io/block-a-coroutine.php) | Waiting (e.g. `sleep()` or `Channel::pop()`) pauses only the current coroutine; the rest of the process keeps running | client |
| [`defer`](examples/csp/defer.php) | Register cleanup callbacks that run, in reverse order, when a coroutine finishes | client |
| [Context](examples/csp/context.php) | Per-coroutine storage that is cleaned up automatically when the coroutine ends; the coroutine-safe replacement for globals | client |
| [Exit from a coroutine](examples/csp/coroutines/exit.php) | `exit()` inside a coroutine throws `Swoole\ExitException` instead of ending the process; prefer throwing your own exception | client |

## Coordinating coroutines

| Example | What it shows | Run from |
|---|---|---|
| [Channel](examples/csp/channel.php) | The main way coroutines pass data to each other; `push()` and `pop()` wait automatically when the channel is full or empty | client |
| [`WaitGroup`](examples/csp/waitgroup.php) | Wait for a group of coroutines to finish, like Go's [`sync.WaitGroup`](https://pkg.go.dev/sync#WaitGroup) | client |
| [`Barrier`](examples/csp/barrier.php) | The same goal as `WaitGroup` with less code: the wait ends once every coroutine has released its reference to the barrier | client |
| [Lock across coroutines](examples/locks/lock-across-coroutines.php) | `Swoole\Coroutine\Lock`: a mutex that suspends only the waiting coroutine, not the whole process | client |

## Runtime hooks

Runtime hooks make existing blocking PHP functions and extensions (curl, PDO, mysqli, Redis, `sleep()`, ...) run
concurrently inside coroutines, without changing their code. In the database and Redis examples below, five
3-second operations finish in about 3 seconds instead of 15.

| Example | What it shows | Run from |
|---|---|---|
| [Enable and disable runtime hooks](examples/csp/coroutines/enable-and-disable.php) | Turn coroutine support on and off in a standalone script (`Swoole\Coroutine\run()` turns all hooks on by default) | client |
| [Hook flags](examples/hooks/hook-flags.php) | Choose which kinds of blocking functions get hooked: coroutines with the hooks enabled run concurrently, the others block | client |

### curl

| Example | What it shows | Run from |
|---|---|---|
| [`SWOOLE_HOOK_NATIVE_CURL`](examples/hooks/native-curl.php) (recommended) | Hooks the real curl extension, including `curl_multi_*()`: six 2-second requests finish in about 2 seconds | client |
| [`SWOOLE_HOOK_CURL`](examples/hooks/curl.php) | The older approach, implemented in [Swoole Library](https://github.com/swoole/library); doesn't support `curl_multi_*()` | client |

### Databases

| Example | What it shows | Run from |
|---|---|---|
| [mysqli](examples/hooks/mysqli.php) | Five concurrent MySQL queries using the mysqli extension | client |
| [PDO_MYSQL](examples/hooks/pdo_mysql.php) | Five concurrent MySQL queries using PDO | client |
| [PDO_PGSQL](examples/hooks/pdo_pgsql.php) | Five concurrent PostgreSQL queries using PDO | client |
| [PDO_SQLITE](examples/hooks/pdo_sqlite.php) | Five concurrent SQLite writes using PDO (the delay comes from waiting on SQLite's own database lock) | client |

### Redis

| Example | What it shows | Run from |
|---|---|---|
| [phpredis](examples/hooks/redis/phpredis.php) | Five concurrent slow Redis operations using the phpredis extension | client |
| [predis](examples/hooks/redis/predis.php) | The same using the pure-PHP predis library | client, after `composer global require predis/predis=~3.0` |

### Connection pools

Once database calls run concurrently, a pool shares a bounded set of connections among the coroutines instead of opening
one connection per coroutine.

| Example | What it shows | Run from |
|---|---|---|
| [MySQL connection pool](examples/pool/database-pool/mysqli.php) | 1,024 one-second queries through 128 pooled connections finish in about 8 seconds | client |
| [PostgreSQL connection pool](examples/pool/database-pool/pdo_pgsql.php) | The same pattern with PDO_PGSQL | client |
| [Redis connection pool](examples/pool/database-pool/redis.php) | The same pattern with phpredis | client |

For a custom pool, see [crowdstar/vertica-swoole-adapter](https://github.com/Crowdstar/vertica-swoole-adapter), which
implements a connection pool for HP Vertica databases through ODBC.

## Coroutine clients

Swoole's own non-blocking clients, for use inside coroutines. Most of them talk to the servers running in the `server`
container; the low-level socket example creates its own server socket.

| Example | What it shows | Run from |
|---|---|---|
| [HTTP/1 client](examples/clients/http1.php) | Two requests made concurrently; the faster one finishes first | client |
| [HTTP/2 client](examples/clients/http2.php) | Multiplex concurrent requests as streams over a single TCP connection | client |
| [WebSocket client](examples/clients/websocket.php) | Connect, send a message, and read the reply | client |
| [TCP client](examples/clients/tcp.php) | Query two TCP servers concurrently | client |
| [UDP client](examples/clients/udp.php) | Send a datagram and read the reply | client |
| [Low-level socket](examples/misc/coroutine-socket.php) | `Swoole\Coroutine\Socket`: a server socket and two client sockets exchanging length-prefixed messages | client |

## Servers

Some servers below are auto-started in the `server` container, and their docblocks show how to send requests to them.
The others ("client" in the Run from column) start their own server on a random port, send it requests, print what
happened, and shut down, so they run like any other script. A server runs in one of two modes: in `SWOOLE_BASE` mode (the default), each worker process accepts and handles its own
connections; in `SWOOLE_PROCESS` mode, a master process owns all the connections and forwards their data to the worker
processes, so any worker can reach any connection (see [WebSocket broadcasting](examples/servers/websocket-broadcast.php)).

### Your first server

| Example | What it shows | Run from |
|---|---|---|
| [HTTP/1 server](examples/servers/http1.php) | Custom status codes, static file serving, and gzip compression | auto-started, port 9501 |
| [Server events](examples/servers/server-events.php) | Which callbacks (`onStart`, `onWorkerStart`, `onReceive`, `onTask`, ...) fire, in which process, and in what order, including during a reload | `docker run`, see docblock |
| [Coroutines in a server](examples/servers/enable-coroutine.php) | The `enable_coroutine`, `task_enable_coroutine`, and `hook_flags` settings, and why a server enables no runtime hooks by default | client |
| [Coroutine-style HTTP server](examples/servers/coroutine-http-server.php) | `Swoole\Coroutine\Http\Server`: a server that runs inside a coroutine in the current process, with no worker processes | client |
| [Hot reload](examples/servers/hot-reload.php) | Load updated code into a running server with `$server->reload()`, which restarts the worker processes gracefully | client |

### One server per protocol

| Example | What it shows | Run from |
|---|---|---|
| [HTTP/2 server](examples/servers/http2.php) | A minimal HTTP/2 server | auto-started, port 9503 |
| [HTTPS server](examples/servers/https.php) | Serve HTTP over SSL/TLS, and see a client reject an untrusted certificate and accept a trusted one | client |
| [Server-Sent Events (SSE)](examples/servers/http1-sse.php) | Stream a response chunk by chunk over HTTP/1.1, the technique many AI chat apps use to stream text | client |
| [WebSocket server](examples/servers/websocket.php) | A minimal WebSocket server that replies to messages | auto-started, port 9504 |
| [WebSocket broadcasting](examples/servers/websocket-broadcast.php) | Send a message to every connected client, across worker processes, as in a chat room | client |
| [TCP server, event-driven style](examples/servers/tcp-event-driven.php) | Register callbacks such as `onReceive`, and let the server call them | auto-started, port 9505 |
| [TCP server, coroutine style](examples/servers/tcp-coroutine-style.php) | Write the accept/read/write loop yourself inside coroutines, like a Go server | auto-started, port 9507 |
| [UDP server](examples/servers/udp.php) | An echo server for UDP datagrams | auto-started, port 9506 |
| [UDP multicast](examples/misc/multicast.php) | A UDP server that joins an IP multicast group and receives datagrams sent to the group address | client |
| [Redis server](examples/servers/redis.php) | A server speaking the Redis protocol (minimal `GET`/`SET`), usable from any Redis client | client |
| [MQTT broker](examples/servers/mqtt.php) | A minimal publish/subscribe broker built on the `open_mqtt_protocol` setting, tested with the Mosquitto command-line clients | auto-started, port 9514 |
| [Reverse proxy](examples/servers/proxy.php) | A TCP-level reverse proxy relaying each connection to the HTTP/1 server | auto-started, port 9520 |

### Multiple ports and protocols

| Example | What it shows | Run from |
|---|---|---|
| [Listening on multiple ports](examples/servers/multiple-ports.php) | One server listening on two ports, each with its own callbacks | client |
| [Different protocols on different ports](examples/servers/mixed-protocols-per-port.php) | One server speaking HTTP on one port and raw TCP on another | client |
| [Several protocols on the same port](examples/servers/mixed-protocols-same-port.php) | HTTP/1, HTTP/2, and WebSocket served on one port | client |

### Connection health and protection

| Example | What it shows | Run from |
|---|---|---|
| [Heartbeat](examples/servers/heartbeat.php) | The server closes connections that have sent nothing for a given number of seconds | server |
| [TCP keepalive](examples/servers/keepalive.php) | Let the operating system probe idle connections and drop dead ones | auto-started, port 9602 |
| [Delayed receive (DDoS protection)](examples/servers/ddos-protection.php) | Delay reading from a new connection until your code approves it (`enable_delay_receive` and `Server::confirm()`) | client |

### State inside a server

| Example | What it shows | Run from |
|---|---|---|
| [APCu caching](examples/servers/apcu-caching.php) | APCu works in Swoole the same way as in any PHP CLI application; per-worker request counters show that its cache is per process | auto-started, port 9513 |

## Processes and shared memory

### Process pools

`Swoole\Process\Pool` keeps a fixed set of worker processes running, restarting any that exit. The examples differ in how
outside code sends work to the workers through IPC (inter-process communication).

| Example | What it shows | Run from |
|---|---|---|
| [Standalone pool](examples/pool/process-pool/pool-standalone.php) | Workers just run your code; nothing is sent to them | client |
| [Pool with a message queue](examples/pool/process-pool/pool-msgqueue.php) | Send work to the pool through a System V message queue | auto-started |
| [Pool with a TCP socket](examples/pool/process-pool/pool-tcp-socket.php) | Send work to the pool over TCP | auto-started, port 9701 |
| [Pool with a Unix socket](examples/pool/process-pool/pool-unix-socket.php) | Send work to the pool over a Unix socket | auto-started |
| [Pool client](examples/pool/process-pool/client.php) | Talks to the three pools above, through the message queue, the TCP socket, and the Unix socket | server |
| [Detach a worker](examples/pool/process-pool/detach.php) | Let a worker escape the pool manager's control to finish a long task at its own pace | client |

### Sharing data between processes

| Example | What it shows | Run from |
|---|---|---|
| [`Swoole\Table`](examples/misc/shared-table.php) | A fixed-schema, in-memory table shared by all processes, with atomic counter updates | client |
| [Atomic counter, unsigned 32-bit](examples/misc/atomic-counter-unsigned-32-bit.php) | `Swoole\Atomic`: a shared-memory counter that is safe to update from many processes | client |
| [Atomic counter, signed 64-bit](examples/misc/atomic-counter-signed-64-bit.php) | `Swoole\Atomic\Long`: the same for signed 64-bit integers | client |

### Synchronizing processes

| Example | What it shows | Run from |
|---|---|---|
| [Lock across processes](examples/locks/lock-across-processes.php) | `Swoole\Lock`: a shared-memory mutex that blocks the whole waiting process (never use it across coroutines; see [Deadlocks](#deadlocks-how-they-happen)) | client |
| [Block one process with a lock](examples/io/block-a-process-using-swoole-lock.php) | Block a process on a `Swoole\Lock` with a timeout | client |
| [Block processes with a lock](examples/io/block-processes-using-swoole-lock.php) | Block several processes on a shared `Swoole\Lock`, then release them one by one from another process | client |
| [Block and wake up a process with an atomic](examples/io/block-processes-using-swoole-atomic.php) | One process blocks on a shared `Swoole\Atomic` with `wait()` (with and without a timeout) until another process wakes it up with `wakeup()` | client |

### Threads

Swoole 6 can also run PHP code in multiple threads (`Swoole\Thread`). Threads don't share PHP variables; they share
data through thread-safe containers instead. These examples need a thread-safe (ZTS) build of PHP, so they run in the
`zts` container.

| Example | What it shows | Run from |
|---|---|---|
| [Lock across threads](examples/locks/lock-across-threads.php) | `Swoole\Thread\Lock`: a mutex shared by threads | zts |
| [Shared map](examples/threads/map.php) | `Swoole\Thread\Map`: four threads update one map; an atomic `incr()` never loses an update, a read-then-write does | zts |
| [Work queue](examples/threads/queue.php) | `Swoole\Thread\Queue`: worker threads take jobs from a shared queue, the basic shape of a thread pool | zts |

## Signals and the event loop

| Example | What it shows | Run from |
|---|---|---|
| [Default exit condition](examples/events/default-exit-condition.php) | Signal listeners alone don't keep a process running: it exits before any signal arrives | client |
| [Custom exit condition](examples/events/customized-exit-condition.php) | The `exit_condition` option keeps the event loop alive while signal listeners are registered | client |
| [Wait for a signal in a coroutine](examples/events/wait-signal.php) | `Swoole\Coroutine\System::waitSignal()` suspends the calling coroutine until a signal arrives or a timeout expires, with no callbacks | client |

## Timers and cron jobs

| Example | What it shows | Run from |
|---|---|---|
| [`Swoole\Timer`](examples/timer/timer-class.php) | Run code after a delay or at a fixed interval, like JavaScript's `setTimeout()` and `setInterval()` | server |
| [Timers with coroutines](examples/timer/coroutine-style.php) | The same, implemented with plain coroutines and sleeps | server |

Cron jobs below are recurring jobs implemented in six different ways, without the system cron.

### Standalone cron jobs

The scheduler is a program of its own, deployed and supervised independently.

| Example | What it shows | Run from |
|---|---|---|
| [Using timers](examples/cronjobs/timer-tick.php) | Fixed-rate scheduling with `Swoole\Timer` | client |
| [Using a coroutine loop](examples/cronjobs/coroutine-sleep.php) | Overlap-free scheduling with a plain coroutine sleep | client |
| [Using an interruptible sleep](examples/cronjobs/interruptible-channel.php) | A channel-based sleep that a shutdown can interrupt instantly | client |
| [Using a process pool](examples/cronjobs/process-pool.php) | The schedule sharded across the workers of a process pool | client |

### Cron jobs inside a server

The scheduler runs inside an application server, following the server's lifecycle and sharing its state.

| Example | What it shows | Run from |
|---|---|---|
| [Timer plus task workers](examples/cronjobs/tick-to-task.php) | A timer in one server worker hands scheduled work to task worker processes, so slow jobs don't stall requests | client |
| [Dedicated user process](examples/cronjobs/user-process.php) | An isolated scheduler process attached to a server (`Server::addProcess()`), with graceful shutdown on `SIGTERM` | client |
| [Interruptible sleep in a server](examples/servers/interruptible-sleep.php) | Let a cron job inside a web server run one last time when the server shuts down, instead of being cut off mid-interval | client |

## Putting it all together

| Example | What it shows | Run from |
|---|---|---|
| [Integrated HTTP/1 server](examples/servers/http1-integrated.php) | One HTTP server that also runs cron jobs, and hands slow work to task worker processes (`task()`, `taskwait()`, `taskWaitMulti()`, `taskCo()`) | auto-started, port 9502 |
| [Integrated WebSocket server](examples/servers/websocket-integrated.php) | A WebSocket server with separate processes for a cron job and for consuming a task queue | auto-started, port 9508 |
| [Rock Paper Scissors](examples/servers/rock-paper-scissors.php) | The server holds the first two players' HTTP requests open, and answers all three players at once when the last one arrives, which PHP-FPM can't do | auto-started, port 9801 (also open on your host: `http://127.0.0.1:9801?name=A`) |

## Pitfalls and advanced topics

### Deadlocks: how they happen

A deadlock happens when every coroutine is waiting and nothing can wake them up. Swoole detects this and reports it.

| Example | What it shows | Run from |
|---|---|---|
| [Pop from an empty channel](examples/csp/deadlocks/an-empty-channel.php) | The only coroutine waits for data that never comes | client |
| [Push to a full channel](examples/csp/deadlocks/channel-is-full.php) | The only coroutine waits for space that never frees up | client |
| [File locking](examples/csp/deadlocks/file-locking.php) | Lock a file that is already locked and never released | client |
| [`Swoole\Lock` across coroutines](examples/csp/deadlocks/swoole-lock.php) | Acquire a locked `Swoole\Lock` from another coroutine: the program just hangs | client |
| [Server shutdown](examples/csp/deadlocks/server-shutdown.php) | Shut down or reload a server improperly | `docker run`, see docblock |

### Deadlocks: detecting and handling them

Each of these examples creates a deadlock on purpose, by pausing the only coroutine in the program forever.

| Example | What it shows | Run from |
|---|---|---|
| [Default behavior](examples/csp/deadlocks/coroutine-yielded-default-behavior.php) | Swoole reports the deadlock and the coroutines involved | client |
| [Deadlock check disabled](examples/csp/deadlocks/coroutine-yielded-deadlock-check-disabled.php) | Turn off the deadlock report with `enable_deadlock_check` | client |
| [Custom exit condition](examples/csp/deadlocks/coroutine-yielded-custom-exit-condition.php) | Replace the deadlock check with your own rule for when the program may exit (this demo waits forever) | client |

### CPU-bound code and scheduling

Coroutines normally switch only when one of them waits on I/O, so a CPU-heavy loop can starve the others. Read these in
order.

| Example | What it shows | Run from |
|---|---|---|
| [Non-preemptive scheduling](examples/csp/scheduling/non-preemptive.php) | The default: a busy coroutine never gives up the CPU, so the other coroutine never runs | client |
| [Preemptive scheduling](examples/csp/scheduling/preemptive.php) | With `swoole.enable_preemptive_scheduler` on, Swoole interrupts the busy coroutine every few milliseconds so others get a turn | client |
| [Toggle the preemptive scheduler](examples/csp/scheduling/toggle-preemptive-scheduler.php) | Turn preemption off around a critical section, and back on afterwards | client |

## Testing Swoole code

With plain PHPUnit, tests run one at a time and outside of any coroutine, so tests that wait on I/O add up, and test
code can't call coroutine APIs directly. [deminy/counit](https://github.com/deminy/counit) runs each test method in its
own coroutine instead, so tests that wait on I/O run concurrently.

This repository's own test suite uses it to run a test for nearly every example:

* [phpunit.xml.dist](phpunit.xml.dist) registers the `Deminy\Counit\CounitExtension` extension.
* The base test class [tests/Support/ExampleTestCase.php](tests/Support/ExampleTestCase.php) extends
  `Deminy\Counit\TestCase`.
* There is one test class per topic under [tests/](tests/). Most tests run their example as a subprocess and check its
  output, with at most 8 examples running at once; tests for the auto-started servers connect to them using Swoole's
  coroutine clients instead.

To run the tests (`composer.json` is at the repository root, hence the `-w /var/www`):

```bash
docker compose exec -T -w /var/www client composer install -n -q --no-progress
docker compose exec -T -w /var/www client ./vendor/bin/counit --testsuite client
docker compose exec -T -w /var/www server ./vendor/bin/counit --testsuite server
docker compose exec -T -w /var/www zts ./vendor/bin/counit --testsuite zts
```

Almost every test is in the `client` suite. The `server` suite holds the one test that needs the `server` container's
own filesystem, and the `zts` suite holds the tests for the thread examples. The client suite takes about a minute.

<details>
<summary>Why some tests run in a separate process, and which example isn't tested</summary>

* Tests for examples that may hang forever by design (the deadlock demos, the process-blocking `io/block-*` examples) or
  that depend on the preemptive scheduler run one at a time in a separate, non-coroutine process, using PHPUnit's
  `#[RunInSeparateProcess]` attribute and `ExampleTestCase::runIsolated()`. Running many such subprocesses concurrently
  proved unreliable, so these trade speed for reliability, and they account for most of the suite's running time.
* `csp/coroutines/benchmark.php` is skipped (it creates 1,000,000 coroutines).

</details>

For examples of unit-testing coroutine code in the same process, see the
[counit documentation](https://github.com/deminy/counit).

## License

The examples are licensed under [CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/); see
[LICENSE.txt](LICENSE.txt).
