<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class CronjobsTest extends ExampleTestCase
{
    public function testCoroutineSleep(): void
    {
        $result = $this->runExample('cronjobs/coroutine-sleep.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Job: run 3 of 3.', $result['output']);
        self::assertStringContainsString('All runs finished; the script is exiting.', $result['output']);
        self::assertStringNotContainsString('Deprecated', $result['output']);
    }

    public function testInterruptibleChannel(): void
    {
        $result = $this->runExample('cronjobs/interruptible-channel.php');
        self::assertSame(0, $result['code'], $result['output']);
        // The message proves the stop request was observed via the closed channel (the CLOSED branch, not a plain
        // pop() timeout), followed by one final job run.
        self::assertStringContainsString('Stop requested; running the job one last time before exiting.', $result['output']);
        self::assertStringContainsString('The cronjob has exited.', $result['output']);
        self::assertStringNotContainsString('Deprecated', $result['output']);
    }

    public function testProcessPool(): void
    {
        $result = $this->runExample('cronjobs/process-pool.php');
        self::assertSame(0, $result['code'], $result['output']);
        // The schedule is sharded across the pool: each job runs in its own worker.
        self::assertStringContainsString('Job A (worker #0)', $result['output']);
        self::assertStringContainsString('Job B (worker #1)', $result['output']);
        // Both workers must take the graceful path: SIGTERM -> closed channel -> one final run -> clean exit.
        self::assertStringContainsString('Worker #0: SIGTERM received; running Job A one last time before exiting.', $result['output']);
        self::assertStringContainsString('Worker #1: SIGTERM received; running Job B one last time before exiting.', $result['output']);
        self::assertStringNotContainsString('Deprecated', $result['output']);
    }

    public function testTickToTask(): void
    {
        $result = $this->runExample('cronjobs/tick-to-task.php');
        self::assertSame(0, $result['code'], $result['output']);
        // The worker-0 guard: exactly one worker registers the timer, the other explicitly skips it.
        self::assertStringContainsString('Worker #0 started; registering the cron timer here.', $result['output']);
        self::assertStringContainsString('Worker #1 started; the cron timer is NOT registered here.', $result['output']);
        // The dispatch chain: tick -> task worker executes -> result back in the dispatching worker via onFinish.
        self::assertStringContainsString('Tick: dispatching run 1 to the task workers.', $result['output']);
        self::assertMatchesRegularExpression('/Task worker #\d+: running scheduled job \(run 1\)\./', $result['output']);
        self::assertStringContainsString('Worker #0: job result received: run 1 done.', $result['output']);
        // Shutdown must be clean: clearing the timers first prevents the worker-exit timeout.
        self::assertStringNotContainsString('forced termination', $result['output']);
        self::assertStringNotContainsString('Deprecated', $result['output']);
    }

    public function testTimerTick(): void
    {
        $result = $this->runExample('cronjobs/timer-tick.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Job A: runs every 1 second.', $result['output']);
        self::assertStringContainsString('Job B: runs every 2 seconds.', $result['output']);
        self::assertStringContainsString('All timers cleared; the script is exiting.', $result['output']);
        // Guards the run() wrapper: without it, the script would fall back to the long-deprecated implicit event
        // loop at script shutdown ("swoole_event_rshutdown(): Event::wait() ... is deprecated").
        self::assertStringNotContainsString('Deprecated', $result['output']);
    }

    public function testUserProcess(): void
    {
        $result = $this->runExample('cronjobs/user-process.php');
        self::assertSame(0, $result['code'], $result['output']);
        // The SIGTERM message proves the graceful-shutdown handshake ran: server shutdown -> SIGTERM to the user
        // process -> channel closed -> one final run -> clean exit.
        self::assertStringContainsString('SIGTERM received; running the job one last time before exiting.', $result['output']);
        self::assertStringContainsString('The cronjob process has exited.', $result['output']);
        self::assertStringNotContainsString('Deprecated', $result['output']);
    }
}
