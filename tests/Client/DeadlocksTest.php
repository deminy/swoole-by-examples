<?php

declare(strict_types=1);

namespace Tests\Client;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Support\ExampleTestCase;

// Deadlock demos with bounded/detectable termination: Swoole's deadlock detector catches these, and each exits
// on its own - confirmed by running each directly during design. The three that hang forever instead
// (swoole-lock.php, file-locking.php, coroutine-yielded-custom-exit-condition.php) are in the timeout section
// below.
class DeadlocksTest extends ExampleTestCase
{
    public function testAnEmptyChannelShowsDeadlockInfoByDefault(): void
    {
        $result = $this->runExample('csp/deadlocks/an-empty-channel.php');
        self::assertStringContainsString('deadlock!', $result['output']);
    }

    public function testAnEmptyChannelHidesDeadlockInfoWithArgZero(): void
    {
        $result = $this->runExample('csp/deadlocks/an-empty-channel.php', ['0']);
        self::assertStringNotContainsString('deadlock!', $result['output']);
    }

    public function testChannelIsFullShowsDeadlockInfoByDefault(): void
    {
        $result = $this->runExample('csp/deadlocks/channel-is-full.php');
        self::assertStringContainsString('deadlock!', $result['output']);
    }

    public function testChannelIsFullHidesDeadlockInfoWithArgZero(): void
    {
        $result = $this->runExample('csp/deadlocks/channel-is-full.php', ['0']);
        self::assertStringNotContainsString('deadlock!', $result['output']);
    }

    public function testCoroutineYieldedDefaultBehaviorShowsDeadlockInfo(): void
    {
        $result = $this->runExample('csp/deadlocks/coroutine-yielded-default-behavior.php');
        self::assertStringContainsString('deadlock!', $result['output']);
    }

    public function testCoroutineYieldedDeadlockCheckDisabledHidesDeadlockInfo(): void
    {
        $result = $this->runExample('csp/deadlocks/coroutine-yielded-deadlock-check-disabled.php');
        self::assertSame("1\n2", trim($result['output']));
    }

    // Takes ~5-10s: Swoole's worker-exit-timeout mechanism forces the stuck worker down before the deadlock
    // message prints (confirmed by testing - it does terminate on its own, unlike the two below).
    public function testServerShutdown(): void
    {
        $result = $this->runExample('csp/deadlocks/server-shutdown.php');
        self::assertStringContainsString('deadlock!', $result['output']);
    }

    // The following three hang forever by design. #[RunInSeparateProcess] + runIsolated() rather than
    // runExample(): see runIsolated()'s docblock for why.

    // Documented to run forever ("It will run forever" in its own docblock).
    #[RunInSeparateProcess]
    public function testSwooleLockHangsForever(): void
    {
        $result = $this->runIsolated('csp/deadlocks/swoole-lock.php', 8.0);
        self::assertTrue($result['timedOut'], $result['output']);
    }

    // Not documented as hanging forever, but confirmed by testing: an flock() wait that Swoole's coroutine
    // deadlock detector can't see (it's a raw OS syscall, not a coroutine-native primitive), so it genuinely
    // never returns.
    #[RunInSeparateProcess]
    public function testFileLockingHangsForever(): void
    {
        $result = $this->runIsolated('csp/deadlocks/file-locking.php', 8.0);
        self::assertTrue($result['timedOut'], $result['output']);
    }

    // Its custom exit condition (coroutine_num === 0) is never met, so the program never exits - confirmed by
    // testing (its own docblock says as much: "The program will never exit since the exit condition won't meet").
    #[RunInSeparateProcess]
    public function testCoroutineYieldedCustomExitConditionRunsForever(): void
    {
        $result = $this->runIsolated('csp/deadlocks/coroutine-yielded-custom-exit-condition.php', 8.0);
        self::assertTrue($result['timedOut'], $result['output']);
    }
}
