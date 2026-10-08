#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to cancel a coroutine from another coroutine, using method \Swoole\Coroutine::cancel().
 *
 * Cancelling a coroutine doesn't kill it. Instead, if the coroutine is suspended waiting for something (sleeping,
 * reading from a socket, popping from a channel, etc.), the waiting operation is interrupted and returns a failure
 * right away; the coroutine then keeps running from there. The coroutine can call \Swoole\Coroutine::isCanceled() to tell a cancellation apart
 * from an ordinary failure, and decide what to do next (usually: clean up and return).
 *
 * In this example:
 *   1. Coroutine #2 starts a 10-second sleep.
 *   2. Coroutine #3 pops data from an empty channel, waiting up to 10 seconds.
 *   3. After 0.5 seconds, the main coroutine cancels both of them.
 * Both waits are interrupted at once, so the script finishes in about 0.5 seconds instead of 10.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "time ./csp/coroutines/cancel.php"
 */

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

run(function (): void {
    $wg = new WaitGroup(2);

    $sleeper = go(function () use ($wg): void {
        $start  = microtime(true);
        $result = Coroutine::sleep(10);
        printf(
            'Coroutine #%d: sleep() returned %s after %.1f seconds; canceled: %s.' . PHP_EOL,
            Coroutine::getCid(), // @phpstan-ignore argument.type
            var_export($result, true),
            microtime(true) - $start,
            var_export(Coroutine::isCanceled(), true),
        );
        $wg->done();
    });

    $popper = go(function () use ($wg): void {
        $channel = new Channel();
        $data    = $channel->pop(10);
        printf(
            'Coroutine #%d: pop() returned %s; canceled: %s; channel error code: %d (SWOOLE_CHANNEL_CANCELED is %d).' . PHP_EOL,
            Coroutine::getCid(), // @phpstan-ignore argument.type
            var_export($data, true),
            var_export(Coroutine::isCanceled(), true),
            $channel->errCode,
            SWOOLE_CHANNEL_CANCELED,
        );
        $wg->done();
    });

    Coroutine::sleep(0.5);
    echo 'Main coroutine: canceling coroutines #', $sleeper, ' and #', $popper, '.', PHP_EOL;
    Coroutine::cancel($sleeper);
    Coroutine::cancel($popper);

    $wg->wait();
    echo 'Done.', PHP_EOL;
});
