<?php

declare(strict_types=1);

namespace Tests\Support;

use Deminy\Counit\TestCase;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\System;

abstract class ExampleTestCase extends TestCase
{
    /**
     * Hard cap on how much output the run*() helpers below accumulate into memory. A defensive backstop, not a
     * normal limit: every example in this suite produces at most a few hundred KB. It exists for the case where
     * an example runs away unexpectedly (confirmed by testing: csp/scheduling/toggle-preemptive-scheduler.php and
     * preemptive.php can do exactly this if Swoole's preemptive scheduler misfires under concurrent load - see
     * runExample()'s docblock) - without this cap, accumulating that runaway output into a single growing PHP
     * string exhausts the 128MB memory_limit. Once hit, the pipes are still drained (so the child never blocks on
     * a full pipe), the data is just discarded instead of appended.
     */
    private const int MAX_OUTPUT_BYTES = 2 * 1024 * 1024;

    /**
     * counit's "global style" gives every test method its own coroutine automatically, with no concurrency cap.
     * Confirmed by testing: running this suite's tests unthrottled segfaults intermittently once past a certain
     * number of concurrent proc_open()+coroutine operations - this looks like a genuine Swoole extension-level
     * issue at scale, not something fixable from userland beyond avoiding the scale that triggers it. A shared
     * semaphore, acquired around each runExample() call, keeps at most MAX_CONCURRENT of them in flight
     * regardless of how many test methods are running.
     */
    private const int MAX_CONCURRENT = 8;

    private static ?Channel $semaphore = null;

    /**
     * Runs an example script to completion (up to $timeout seconds - a safety net; most examples take a few
     * seconds at most) and returns its exit code and combined stdout+stderr. Runs inside a coroutine (via a
     * shared semaphore, see MAX_CONCURRENT above) - do not call this from a #[RunInSeparateProcess] test method;
     * those run in a plain, non-coroutine child process where Swoole's coroutine APIs error out ("API must be
     * called in the coroutine", confirmed by testing). Use runIsolated() there instead.
     *
     * The default of 60s is generous for the common case, but a few examples need a tighter bound: examples that
     * rely on Swoole's preemptive scheduler (csp/scheduling/toggle-preemptive-scheduler.php, preemptive.php) depend
     * on a timing-sensitive signal firing reliably, which - confirmed by testing - becomes unreliable when many
     * *other* proc_open() children are being spawned/killed concurrently elsewhere in this same test run. When the
     * preemptive scheduler doesn't fire, these examples' busy loop keeps running (and printing) indefinitely
     * instead of exiting after ~100,000 lines, and at a 60s bound that means tens of megabytes of accumulated
     * output - one run hit 116MB, another hit PHP's 128MB memory_limit outright. Pass a tighter $timeout for
     * exactly those two examples so a scheduler misfire surfaces as a fast, clean test failure instead of a slow
     * OOM.
     *
     * @param list<string> $args
     * @return array{code: int, output: string}
     */
    protected function runExample(string $path, array $args = [], float $timeout = 60.0): array
    {
        self::$semaphore ??= new Channel(self::MAX_CONCURRENT);
        self::$semaphore->push(true);
        try {
            $result = $this->pollUntilDone($path, $args, $timeout, static function (float $remaining): void {
                System::sleep(min(0.01, $remaining));
            });
        } finally {
            self::$semaphore->pop();
        }

        self::assertFalse($result['timedOut'], "example did not finish within {$timeout}s:\n" . substr($result['output'], 0, 2000));
        self::assertNotNull($result['code']);

        // proc_get_status()['exitcode'] is -1 (never a real exit code - those are always 0-255) whenever the
        // process was terminated BY A SIGNAL instead of exiting normally; investigated in depth after a one-off
        // CI failure on pool/process-pool/detach.php (code -1, cause never conclusively identified - ruled out
        // Swoole's own Pool::shutdown()/SIGTERM handling by reading its source, which is safe). Prepending the
        // signal here means the NEXT such failure explains itself in the assertSame(0, $result['code'], ...)
        // message instead of leaving a bare, mysterious "-1".
        $output = $result['output'];
        if ($result['signaled']) {
            $output = "[process was terminated by signal {$result['termsig']}, not a normal exit]\n" . $output;
        }

        return ['code' => $result['code'], 'output' => $output];
    }

    /**
     * Runs an example script that may hang forever by design, from inside a #[RunInSeparateProcess] test method.
     * Confirmed by testing: such a method's body runs in a genuinely separate, non-coroutine PHP process (spawned
     * by PHPUnit itself), so none of Swoole's coroutine APIs - including the Channel-based semaphore runExample()
     * uses - are available there. This method uses only plain blocking PHP (proc_open(), usleep(),
     * proc_get_status()), which needs no coroutine context and needs no concurrency limit of its own: each
     * #[RunInSeparateProcess] test already runs by itself in its own process, sequentially relative to the rest
     * of the suite (that's what the attribute is for), so there's no concurrent proc_open() pressure to guard
     * against here the way there is in runExample().
     *
     * @param list<string> $args
     * @return array{timedOut: bool, code: ?int, output: string, signaled: bool, termsig: ?int}
     */
    protected function runIsolated(string $path, float $timeout, array $args = []): array
    {
        return $this->pollUntilDone($path, $args, $timeout, static function (float $remaining): void {
            usleep((int) (min(0.01, $remaining) * 1_000_000));
        });
    }

    /**
     * Shared by both public methods above. $sleep is the only thing that differs between them: a coroutine-aware
     * yield for runExample() (so other coroutines can run while this one waits), or a plain blocking usleep() for
     * runIsolated() (there's no coroutine scheduler to yield to in that context).
     *
     * Array form (not a shell string) is required for proc_open(): a string command runs through `/bin/sh -c`,
     * whose PID is the shell's, not the actual `php` process; confirmed by testing that killing the wrong PID
     * leaves the real process running as an orphan, still writing to the pipe forever.
     *
     * The stdout/stderr pipes are drained on every loop iteration rather than only once at the end: examples like
     * csp/scheduling/toggle-preemptive-scheduler.php produce 100,000+ lines of output, comfortably more than a
     * pipe's OS buffer (~64KB) - without continuous draining, the child blocks forever inside its own write() call
     * once that buffer fills, which is a real deadlock, not just slowness (confirmed by testing).
     *
     * Exit status is checked via plain proc_get_status()['running'], not Swoole\Coroutine\System::waitPid() with
     * a 0.0 timeout: confirmed by testing that a 0.0 timeout does not mean "check instantly and return" the way
     * it does in many similar APIs - it blocks indefinitely on a still-running process. proc_get_status() doesn't
     * have this problem since it isn't a Swoole coroutine primitive - it's a plain PHP status query that returns
     * immediately regardless, which is also exactly why it works in both the coroutine and non-coroutine cases.
     *
     * @param list<string> $args
     * @param callable(float): void $sleep
     * @return array{timedOut: bool, code: ?int, output: string, signaled: bool, termsig: ?int}
     */
    private function pollUntilDone(string $path, array $args, float $timeout, callable $sleep): array
    {
        /** @var list<string> $command */
        $command = ['php', '/var/www/examples/' . $path, ...$args];

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc        = proc_open($command, $descriptors, $pipes);
        self::assertNotFalse($proc, 'proc_open() failed.');
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output   = '';
        $deadline = microtime(true) + $timeout;
        $exited   = false;
        $exitCode = null;
        $signaled = false;
        $termsig  = null;

        while (true) {
            $output = self::appendCapped($output, stream_get_contents($pipes[1]));
            $output = self::appendCapped($output, stream_get_contents($pipes[2]));

            $status = proc_get_status($proc);
            if (!$status['running']) {
                $exited   = true;
                $exitCode = $status['exitcode'];
                // A process killed by a signal (rather than exiting normally) has no real exit code: 'exitcode'
                // is -1 in that case, and the actual cause is only visible via these two fields. Captured here
                // (see runExample()'s comment above its return statement for why) so a future flake shows WHICH
                // signal instead of a bare "-1" that looks like proc_get_status() malfunctioned.
                $signaled = $status['signaled'];
                $termsig  = $status['termsig'];
                break;
            }

            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                break;
            }

            $sleep($remaining);
        }

        $output = self::appendCapped($output, stream_get_contents($pipes[1]));
        $output = self::appendCapped($output, stream_get_contents($pipes[2]));

        if (!$exited) {
            $pid = (int) proc_get_status($proc)['pid'];
            posix_kill($pid, SIGKILL);
            $output = self::appendCapped($output, stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        return [
            'timedOut' => !$exited,
            'code'     => $exitCode,
            'output'   => $output,
            'signaled' => $signaled,
            'termsig'  => $termsig,
        ];
    }

    private static function appendCapped(string $output, string $chunk): string
    {
        if ($chunk === '' || strlen($output) >= self::MAX_OUTPUT_BYTES) {
            return $output;
        }

        return $output . substr($chunk, 0, self::MAX_OUTPUT_BYTES - strlen($output));
    }
}
