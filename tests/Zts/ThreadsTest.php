<?php

declare(strict_types=1);

namespace Tests\Zts;

use Tests\Support\ExampleTestCase;

// The examples covered here need a thread-safe (ZTS) build of PHP/Swoole, which the `client` and `server` containers
// don't provide, so this test suite ("zts") runs in the `zts` container (phpswoole/swoole:6.2-php8.4-zts) instead.
class ThreadsTest extends ExampleTestCase
{
    public function testLockAcrossThreads(): void
    {
        $result = $this->runExample('locks/lock-across-threads.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }

    public function testMap(): void
    {
        $result = $this->runExample('threads/map.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Counter "atomic" (updated with incr()): 40000', $result['output']);
        // The "unsafe" counter loses a varying number of updates, so only its presence is checked.
        self::assertMatchesRegularExpression('/Counter "unsafe" \(read, then written back\): \d+ /', $result['output']);
        self::assertStringContainsString('Entries written by the threads: thread-1, thread-2, thread-3, thread-4', $result['output']);
    }

    public function testQueue(): void
    {
        $result = $this->runExample('threads/queue.php');
        self::assertSame(0, $result['code'], $result['output']);
        // Which worker thread handles which job depends on timing, so only the number of results is checked.
        self::assertSame(9, preg_match_all('/^Worker thread #[1-3] squared \d: \d+$/m', $result['output']));
        self::assertStringContainsString('All results, in order: 1, 4, 9, 16, 25, 36, 49, 64, 81', $result['output']);
    }
}
