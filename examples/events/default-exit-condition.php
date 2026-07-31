#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows that registering signal listeners alone does not keep a process running.
 *
 * Method \Swoole\Process::signal() registers an asynchronous signal listener on the event loop. However, under
 * Swoole's default exit condition (the check deciding whether the event loop should keep running), signal
 * listeners alone do not count: once there is nothing else left to wait for, the event loop simply exits, even
 * though signal listeners are still registered. As a result, this script reaches its end and the process exits
 * immediately; the two signal handlers below never get a chance to run.
 *
 * Example "customized-exit-condition.php" shows how to change this behavior with a customized exit condition,
 * so that a process can stay running just to listen for signals.
 *
 * How to run this script (the process exits immediately, without waiting for any signal):
 *     docker compose exec -t client bash -c "./events/default-exit-condition.php"
 *
 * @see https://github.com/swoole/swoole-src/issues/2918#issuecomment-546862737
 */

use Swoole\Event;
use Swoole\Process;

$handler = function (int $signal): void {
    echo 'Signal received: ', $signal, PHP_EOL; // This will never be printed out.
};
Process::signal(SIGINT, $handler);
Process::signal(SIGTERM, $handler);

echo 'Two signal listeners are registered.', PHP_EOL;

// The event loop exits immediately: by default, signal listeners alone do not keep it running.
//
// NOTE: In most cases it's not necessary nor recommended to use method `Swoole\Event::wait()` directly in your code.
// The example in this file is just for demonstration purpose.
Event::wait();

echo 'The event loop has exited; the process now ends without any signal ever being handled.', PHP_EOL;
