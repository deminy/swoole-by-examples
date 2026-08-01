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

    // Also exercises the persistent servers/http2.php Supervisord program.
    public function testHttp2(): void
    {
        $result = $this->runExample('clients/http2.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Request #1 sent on stream #1.', $result['output']);
        self::assertStringContainsString('Request #2 sent on stream #3.', $result['output']);
        self::assertStringContainsString('Request #3 sent on stream #5.', $result['output']);
        self::assertStringContainsString('Received a response on stream #1 with status code 200.', $result['output']);
        self::assertStringContainsString('Received a response on stream #3 with status code 200.', $result['output']);
        self::assertStringContainsString('Received a response on stream #5 with status code 200.', $result['output']);
    }

    // Also exercises the persistent servers/tcp-event-driven.php and servers/tcp-coroutine-style.php Supervisord
    // programs (this script connects to both, ports 9505 and 9507).
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
