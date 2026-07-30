<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

// pool/process-pool/client.php is NOT covered here - see tests/Server/PoolProcessTest.php; it must run from the
// `server` container.
class PoolTest extends ExampleTestCase
{
    public function testDatabasePoolMysqli(): void
    {
        $result = $this->runExample('pool/database-pool/mysqli.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testDatabasePoolPdoPgsql(): void
    {
        $result = $this->runExample('pool/database-pool/pdo_pgsql.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testDatabasePoolRedis(): void
    {
        $result = $this->runExample('pool/database-pool/redis.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testProcessPoolDetach(): void
    {
        $result = $this->runExample('pool/process-pool/detach.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('is detaching from the pool', $result['output']);
        self::assertStringContainsString('is running a long task independently', $result['output']);
        // The initial two workers plus the replacement forked after the detach.
        self::assertGreaterThanOrEqual(3, substr_count($result['output'], 'started.'), $result['output']);
        // The detached worker's final "finished its long task" message is deliberately NOT asserted: the pool
        // manager exits as soon as the replacement worker starts, without waiting for the detached worker (that
        // being the very behavior the example demonstrates), so that message is printed ~2s after the example's
        // main process has exited - past the point where runExample() stops capturing output.
    }

    public function testProcessPoolStandalone(): void
    {
        $result = $this->runExample('pool/process-pool/pool-standalone.php');
        self::assertSame(0, $result['code'], $result['output']);
    }
}
