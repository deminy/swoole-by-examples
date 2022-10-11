#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use class \Swoole\Atomic as atomic lock-free counters properly.
 *
 * Class \Swoole\Atomic is used as atomic lock-free counters. It protects an underlying int value by providing methods
 * that perform atomic operations on the value.
 *
 * The class uses shared memory to store counters, and the counters can be accessed by multiple processes. There is no
 * need to use locks since the implementation is based on built-in atomic operations in gcc/clang.
 *
 * When using the counters in worker processes of a Swoole server, they must be created before method Server::start() is
 * called. When using the counters in Process objects, they must be created before method Process::start() is called.
 *
 * Class \Swoole\Atomic uses unsigned 32-bit integers to store the value. To store the value using signed 64-bit
 * integers, use class \Swoole\Atomic\Long instead. Note that class \Swoole\Atomic\Long doesn't have method wait() nor
 * wakeup() implemented.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./misc/atomic-counter-unsigned-32-bit.php"
 */

// The counter is initialized with 0.
$atomic = new Swoole\Atomic();

$atomic->add(5);
echo $atomic->get(), PHP_EOL; // 5

$atomic->add(0);
echo $atomic->get(), PHP_EOL; // 5

$atomic->add(-2);
echo $atomic->get(), PHP_EOL; // 3

$atomic->sub(2);
echo $atomic->get(), PHP_EOL; // 1

$atomic->sub(0);
echo $atomic->get(), PHP_EOL; // 1

$atomic->sub(-1);
echo $atomic->get(), PHP_EOL; // 2

$atomic->set(5);
echo $atomic->get(), PHP_EOL; // 5

// The counter won't be changed to 7 since current value is not 4.
$atomic->cmpset(4, 7);
echo $atomic->get(), PHP_EOL; // 5

// The counter will be changed to 7 since current value is 5 (same as the one expected).
$atomic->cmpset(5, 7);
echo $atomic->get(), PHP_EOL, PHP_EOL; // 7

// At this point, class \Swoole\Atomic works exactly the same as class \Swoole\Atomic\Long.
// Now let's show the difference between the two classes.

$atomic = new Swoole\Atomic(-1);
echo $atomic->get(), PHP_EOL; // 4294967295

$atomic = new Swoole\Atomic();

$atomic->sub(11);
echo $atomic->get(), PHP_EOL; // 4294967285

$atomic->set(-1999);
echo $atomic->get(), PHP_EOL; // 4294965297
