<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

// examples/locks/lock-across-threads.php needs a ZTS build of PHP; it is covered by tests/Zts/ThreadsTest.php, which
// runs in the `zts` container.
class LocksTest extends ExampleTestCase
{
    public function testLockAcrossCoroutines(): void
    {
        $result = $this->runExample('locks/lock-across-coroutines.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }

    public function testLockAcrossProcesses(): void
    {
        $result = $this->runExample('locks/lock-across-processes.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }
}
