#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to keep a process running just to listen for signals, using a customized exit
 * condition.
 *
 * As shown in example "default-exit-condition.php", signal listeners alone do not keep the event loop running: by
 * default the process exits once there is nothing else left to wait for. The Swoole option "exit_condition"
 * customizes that decision: the event loop keeps running for as long as the given callback returns false. Here
 * the callback returns true only once no signal listeners are left, so the process stays alive purely to wait
 * for signals.
 *
 * The signal handler prints the signal received and then unregisters both signal listeners (by registering a
 * NULL handler); that brings the number of signal listeners down to zero, the exit condition becomes true, and
 * the event loop (and thus the process) exits cleanly. The handler also clears any pending timer: a pending
 * timer keeps the event loop running even when the exit condition is already true and, with the listeners
 * unregistered, the SIGTERM signal sent by the timer below would kill the process instead of being handled.
 *
 * In this script, a one-off timer sends the process a SIGTERM signal after 1 second, simulating an external
 * "kill" command (when running the script interactively, pressing Ctrl+C to send a SIGINT signal works the
 * same way). The whole script takes about 1 second to finish.
 *
 * For a coroutine-style, blocking way to wait for signals (without callbacks), please check example
 * "wait-signal.php" in the same folder.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./events/customized-exit-condition.php"
 *
 * @see https://github.com/swoole/swoole-src/issues/2918#issuecomment-546862737
 */

use Swoole\Constant;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;

Coroutine::set(
    [
        // Keep the event loop running for as long as there are signal listeners registered.
        Constant::OPTION_EXIT_CONDITION => function (): bool {
            return Coroutine::stats()['signal_listener_num'] === 0; // @phpstan-ignore offsetAccess.nonOffsetAccessible
        },
    ]
);

$handler = function (int $signal): void {
    echo 'Signal received: ', $signal, PHP_EOL;
    // Unregister both signal listeners, making the exit condition above return true.
    Process::signal(SIGINT, null);
    Process::signal(SIGTERM, null);
    // Clear the pending timer, in case the signal arrives (e.g., through Ctrl+C) before the timer below has
    // fired: a pending timer keeps the event loop running, and with the signal listeners just unregistered,
    // the SIGTERM signal the timer sends would kill the process instead of being handled.
    Timer::clearAll();
};
Process::signal(SIGINT, $handler);
Process::signal(SIGTERM, $handler);

echo 'Two signal listeners are registered; the process now waits for signals.', PHP_EOL;

// Send this very process a SIGTERM signal after 1 second, simulating an external "kill" command.
Timer::after(1_000, function (): void {
    Process::kill(posix_getpid(), SIGTERM);
});

// Unlike in example "event-listening-1.php", here the event loop keeps running until the exit condition is met.
Event::wait();

echo 'All signal listeners are unregistered; the process now exits.', PHP_EOL;
