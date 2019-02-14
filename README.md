# Swoole by Examples

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/deminy/swoole-by-examples/blob/master/LICENSE.txt)

## Setup the PHP Environments

Run following command to launch a Docker container with PHP environments built in:

```bash
docker-compose up -d
```

There are 4 ports mapped:

* port 80: an Nginx web server with PHP-FPM running behind.
    * http://127.0.0.1/index.php
    * http://127.0.0.1/phpinfo.php
* port 81: an Nginx web server with Swoole running behind.
    * http://127.0.0.1:81/index.php
* port 8000: This port is mapped for benchmarking server sockets created with PHP and Swoole.
* port 9501: an HTTP server created with Swoole.
    * http://127.0.0.1:9501/index.php

## Run Sample Scripts

### Sleep

```bash
# The script takes about 3 seconds to finish, and prints out "12".
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php php/sleep.php"
# The script takes about 2 seconds to finish, and prints out "21".
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/sleep1.php"
# The script takes about 2 seconds to finish, and prints out "123456".
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/sleep2.php"
```

### Server Socket

Create a server socket with one of following two commands:

```bash
# Create a server socket on port 8000 with PHP.
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php php/socket.php"
# Create a server socket on port 8000 with Swoole.
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/socket.php"
```

Now use the _ab_ command to benchmark the server socket created:

```bash
ab -n 5000 -c  500 http://127.0.0.1:8000/ # Fire 500 concurrent HTTP requests.
ab -n 5000 -c 1000 http://127.0.0.1:8000/ # Fire 1,000 concurrent HTTP requests.
ab -n 5000 -c 3000 http://127.0.0.1:8000/ # Fire 3,000 concurrent HTTP requests.
```

### Coroutines in a For Loop

```bash
# The script takes about 1 second to finish, with 2,000 coroutines created.
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/for.php"
```

### Nested Coroutines

```bash
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/nested1.php"
# The script takes about 5 seconds to finish.
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/nested2.php"
# The script takes about 5 seconds to finish, and prints out "127983465".
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/nested3.php"
# The script takes about 5 seconds to finish, and prints out "123456789".
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/nested4.php"
```

### Channels

```bash
# At some point during the execution, there are 3 coroutines paused.
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/channel.php"
```

### Defer

```bash
# The script takes about 1 second to finish, and prints out "123456".
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/defer.php"
```

### Enable Coroutine at Runtime

```bash
# The script takes about 2 seconds to finish. Without coroutine enabled at runtime, it takes about 3 seconds to finish.
docker exec -t $(docker ps | grep app | awk '{print $1}') bash -c "time php swoole/enable-coroutine.php"
```
