<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class ClientsTest extends ExampleTestCase
{
    // Also exercises the persistent servers/http1.php Supervisord program.
    public function testHttp1(): void
    {
        $result = $this->runExample('clients/http1.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    // Also exercises the persistent servers/tcp1.php and servers/tcp2.php Supervisord programs (this script
    // connects to both, ports 9505 and 9507).
    public function testTcp(): void
    {
        $result = $this->runExample('clients/tcp.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    // Also exercises the persistent servers/udp.php Supervisord program.
    public function testUdp(): void
    {
        $result = $this->runExample('clients/udp.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    // Also exercises the persistent servers/websocket.php Supervisord program.
    public function testWebsocket(): void
    {
        $result = $this->runExample('clients/websocket.php');
        self::assertSame(0, $result['code'], $result['output']);
    }
}
