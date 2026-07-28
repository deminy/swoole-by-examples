<?php

declare(strict_types=1);

namespace Tests\Client;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\Support\ExampleTestCase;

class IoTest extends ExampleTestCase
{
    public function testBlockingIo(): void
    {
        $result = $this->runExample('io/blocking-io.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12', trim($result['output']));
    }

    public function testBlockingVsNonBlocking(): void
    {
        $result = $this->runExample('io/blocking-vs-non-blocking.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testNonBlockingIo(): void
    {
        $result = $this->runExample('io/non-blocking-io.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('21', trim($result['output']));
    }

    public function testNonBlockingIoDebug(): void
    {
        $result = $this->runExample('io/non-blocking-io-debug.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('123456', trim($result['output']));
    }

    public function testBlockACoroutine(): void
    {
        $result = $this->runExample('io/block-a-coroutine.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    // The following three block the whole OS process by design (that's the point of each example) and normally
    // finish in 2-5 seconds; a timeout here is a real failure, not the expected outcome. #[RunInSeparateProcess]
    // + runIsolated() rather than runExample(): see runIsolated()'s docblock for why.

    #[RunInSeparateProcess]
    public function testBlockAProcessUsingSwooleLock(): void
    {
        $result = $this->runIsolated('io/block-a-process-using-swoole-lock.php', 10.0);
        self::assertFalse($result['timedOut'], $result['output']);
        self::assertSame(0, $result['code'], $result['output']);
    }

    #[RunInSeparateProcess]
    public function testBlockProcessesUsingSwooleLock(): void
    {
        $result = $this->runIsolated('io/block-processes-using-swoole-lock.php', 15.0);
        self::assertFalse($result['timedOut'], $result['output']);
        self::assertSame(0, $result['code'], $result['output']);
    }

    #[RunInSeparateProcess]
    public function testBlockProcessesUsingSwooleAtomic(): void
    {
        $result = $this->runIsolated('io/block-processes-using-swoole-atomic.php', 15.0);
        self::assertFalse($result['timedOut'], $result['output']);
        self::assertSame(0, $result['code'], $result['output']);
    }
}
