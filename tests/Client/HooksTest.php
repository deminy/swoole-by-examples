<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class HooksTest extends ExampleTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // examples/hooks/redis/predis.php needs predis/predis installed globally; it isn't baked into the client image.
        shell_exec('composer global require -n -q --no-progress -- predis/predis=~3.0 2>&1');
    }

    public function testCurl(): void
    {
        $result = $this->runExample('hooks/curl.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testHookFlags(): void
    {
        $result = $this->runExample('hooks/hook-flags.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testMysqli(): void
    {
        $result = $this->runExample('hooks/mysqli.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testNativeCurl(): void
    {
        $result = $this->runExample('hooks/native-curl.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testPdoMysql(): void
    {
        $result = $this->runExample('hooks/pdo_mysql.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testPdoPgsql(): void
    {
        $result = $this->runExample('hooks/pdo_pgsql.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testPdoSqlite(): void
    {
        $result = $this->runExample('hooks/pdo_sqlite.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testRedisPhpredis(): void
    {
        $result = $this->runExample('hooks/redis/phpredis.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testRedisPredis(): void
    {
        $result = $this->runExample('hooks/redis/predis.php');
        self::assertSame(0, $result['code'], $result['output']);
    }
}
