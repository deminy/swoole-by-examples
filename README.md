# Swoole by Examples

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/deminy/swoole-by-examples/blob/master/LICENSE.txt)

NOTE: I'm adding new examples for latest versions of Swoole. For the talk "_Swoole 4: Introducing a New Coroutine Design Pattern in PHP_" I gave at [SunshinePHP 2019](http://sunshinephp.com) and [ConFoo 2019](https://confoo.ca/en/yul2019), you may find the slides [here](https://www.swoole.co.uk/article/coroutine-design-pattern) and the examples under branch [conferences-2019](https://github.com/deminy/swoole-by-examples/tree/conferences-2019).

## Setup the Development Environment

We use [the official Docker image of Swoole](https://hub.docker.com/r/phpswoole/swoole) to run the examples. There are
tens of examples under repository [swoole/docker-swoole](https://github.com/swoole/docker-swoole) shown how to use the
image. Please spend some time checking it first.

Before running the examples, please run command `docker-compose up -d` under the root repository directory to start a
Docker container.

## List of Examples

* CSP programming
    * from blocking I/O to non-blocking I/O
    * [coroutines in a for loop](https://github.com/deminy/swoole-by-examples/blob/master/examples/for.php)
    * nested coroutines
    * Enable coroutines at runtime
    * channels
    * waitGroup
    * defer
* Server-side programming
