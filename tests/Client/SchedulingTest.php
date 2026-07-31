<?php

declare(strict_types=1);

namespace Tests\Client;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Support\ExampleTestCase;

class SchedulingTest extends ExampleTestCase
{
    // csp/scheduling/toggle-preemptive-scheduler.php and preemptive.php both end by design in an uncaught
    // "Quitting." exception (that's the demonstrated point: the busy coroutine eventually gets preempted, letting
    // the second coroutine run and throw) - confirmed by running both directly: exit code 255, but bounded (a few
    // hundred KB of counted-up-integer output, not unbounded, and well under a second). The exit code is
    // intentionally not asserted here.
    //
    // #[RunInSeparateProcess] + runIsolated() rather than runExample(): both examples rely on Swoole's preemptive
    // scheduler actually firing, which is timing-sensitive and - confirmed by testing - becomes unreliable when
    // *any* other proc_open() children are being spawned/killed concurrently elsewhere in this run, even after
    // moving the other timeout-heavy tests off the shared coroutine pool. Running fully isolated, with nothing
    // else concurrent, is what actually gives the scheduler a fair shot at firing on time. Without that, a
    // misfire lets the busy loop run for the full timeout instead, accumulating tens of megabytes of output (one
    // run hit 116MB, another hit PHP's memory_limit outright) - hence the still-tight 15s bound below as a
    // backstop even in isolation.

    #[RunInSeparateProcess]
    public function testTogglePreemptiveScheduler(): void
    {
        $result = $this->runIsolated('csp/scheduling/toggle-preemptive-scheduler.php', 15.0);
        self::assertFalse($result['timedOut'], $result['output']);
        self::assertStringContainsString('Uncaught Exception: Quitting.', $result['output']);
    }

    #[RunInSeparateProcess]
    public function testPreemptive(): void
    {
        $result = $this->runIsolated('csp/scheduling/preemptive.php', 15.0);
        self::assertFalse($result['timedOut'], $result['output']);
        self::assertStringContainsString('Uncaught Exception: Quitting.', $result['output']);
    }

    // Unlike the two above, non-preemptive.php's docblock says outright "the second coroutine has no chance of
    // getting executed. The script will keep printing out integers" - confirmed by testing: no preemptive
    // scheduler is enabled, so the busy loop coroutine never yields and the script runs forever. A timeout here
    // is the expected pass condition. #[RunInSeparateProcess] + runIsolated() rather than runExample(): see
    // runIsolated()'s docblock for why.
    #[RunInSeparateProcess]
    public function testNonPreemptiveRunsForever(): void
    {
        $result = $this->runIsolated('csp/scheduling/non-preemptive.php', 8.0);
        self::assertTrue($result['timedOut'], $result['output']);
    }
}
