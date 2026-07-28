<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

// pool/process-pool/client.php is NOT covered here — see tests/Server/PoolProcessTest.php; it must run from the
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

    public function testProcessPoolStandalone(): void
    {
        $result = $this->runExample('pool/process-pool/pool-standalone.php');
        self::assertSame(0, $result['code'], $result['output']);
    }
}
