<?php

declare(strict_types=1);

namespace Tests\Client;

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Client as TcpClient;
use Swoole\Coroutine\Http2\Client as Http2Client;
use Swoole\Coroutine\Http\Client as HttpClient;
use Swoole\Http2\Request as Http2Request;
use Swoole\Http2\Response as Http2Response;
use Swoole\WebSocket\Frame;
use Tests\Support\ExampleTestCase;

use function Swoole\Coroutine\go;

class ServersTest extends ExampleTestCase
{
    // Not Supervisord-managed; a standalone script that starts its own TCP server AND client internally and
    // self-terminates in ~5s with deterministic output.
    public function testHeartbeat(): void
    {
        $result = $this->runExample('servers/heartbeat.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Server side has successfully closed the connection', $result['output']);
    }

    // Not Supervisord-managed; fully self-driving (its own curl request, reload, and shutdown are all scheduled
    // internally via timers) - confirmed by testing: completes in ~150ms on its own.
    public function testServerEvents(): void
    {
        $result = $this->runExample('servers/server-events.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Event "onShutdown" is triggered.', $result['output']);
    }

    public function testDdosProtection(): void
    {
        $client = new HttpClient('server', 9510);
        $client->set(['timeout' => 5]);
        $ok = $client->get('/');
        self::assertTrue($ok);
        self::assertSame(200, $client->statusCode);
        self::assertSame('OK', trim((string) $client->body));
    }

    public function testKeepalive(): void
    {
        $client = new TcpClient(SWOOLE_SOCK_TCP);
        $client->set(['timeout' => 5]);
        self::assertTrue($client->connect('server', 9602, 5));
        $client->send('ping');
        $response = $client->recv();
        $client->close();
        self::assertSame('ping', $response);
    }

    public function testRedisServer(): void
    {
        $client = new \Redis();
        self::assertTrue($client->connect('server', 6379, 5.0));
        $key   = 'validate-examples-' . uniqid();
        $value = 'bar-' . uniqid();
        $client->set($key, $value);
        self::assertSame($value, $client->get($key));
    }

    public function testHttp2(): void
    {
        $client = new Http2Client('server', 9503);
        $client->set(['timeout' => 5]);
        self::assertTrue($client->connect());

        $request       = new Http2Request();
        $request->path = '/';
        $client->send($request);
        $response = $client->recv();

        self::assertInstanceOf(Http2Response::class, $response);
        self::assertStringContainsString('In this example we start an HTTP/2 server.', (string) $response->data);
    }

    // Deliberately a light smoke check, not a full behavioral test of the 19-second cron timing.
    public function testInterruptibleSleep(): void
    {
        $client = new HttpClient('server', 9512);
        $client->set(['timeout' => 5]);
        $ok = $client->get('/');
        self::assertTrue($ok);
        self::assertSame(200, $client->statusCode);
    }

    public function testApcuCaching(): void
    {
        $jobs = [];
        for ($i = 0; $i < 10; $i++) {
            $jobs[] = static function (): bool {
                $client = new HttpClient('server', 9513);
                $client->set(['timeout' => 5]);
                $ok = $client->get('/');
                return $ok && $client->statusCode === 200 && trim((string) $client->body) === 'OK';
            };
        }

        $results = [];
        $chan    = new Channel(count($jobs));
        foreach ($jobs as $job) {
            go(function () use ($job, $chan): void {
                $chan->push($job());
            });
        }
        for ($i = 0; $i < count($jobs); $i++) {
            $results[] = $chan->pop();
        }
        self::assertNotContains(false, $results, '10 concurrent GET / requests: not all returned "OK"');

        $client = new HttpClient('server', 9513);
        $client->set(['timeout' => 5]);
        $client->get('/summary');
        self::assertSame(200, $client->statusCode);
        self::assertMatchesRegularExpression('/counter_\d+: \d+/', (string) $client->body);
    }

    public function testMixedProtocols(): void
    {
        $http = new HttpClient('server', 9511);
        $http->set(['timeout' => 5]);
        $http->post('/', 'World');
        self::assertSame(200, $http->statusCode);
        self::assertSame('Hello, World', trim((string) $http->body));

        $http2 = new Http2Client('server', 9511);
        $http2->set(['timeout' => 5, 'open_http2_protocol' => true]);
        self::assertTrue($http2->connect());
        $request          = new Http2Request();
        $request->method  = 'POST';
        $request->path    = '/';
        $request->data    = 'World';
        $http2->send($request);
        $response = $http2->recv();
        self::assertInstanceOf(Http2Response::class, $response);
        self::assertSame('Hello, World', trim((string) $response->data));

        $ws = new HttpClient('server', 9511);
        $ws->set(['timeout' => 5]);
        self::assertTrue($ws->upgrade('/'));
        $ws->push('Test');
        $frame = $ws->recv();
        self::assertInstanceOf(Frame::class, $frame);
        self::assertSame('Hello, Test', $frame->data);
    }

    // The proxy forwards raw bytes to the HTTP/1 server (127.0.0.1:9501 inside the "server" container), whose
    // customized "234 Test" status line is relayed back - proving the request really went through the proxy.
    public function testProxy(): void
    {
        $client = new HttpClient('server', 9520);
        $client->set(['timeout' => 5]);
        $ok = $client->get('/');
        self::assertTrue($ok);
        self::assertSame(234, $client->statusCode);
        self::assertNotEmpty((string) $client->body);
    }

    // One server, a different protocol per port: 9550 speaks HTTP, while 9551 has the inherited HTTP protocol
    // switched off and echoes raw bytes. Sending an HTTP request to the TCP port and getting it back VERBATIM
    // (rather than parsed and responded to) proves the per-port protocol override is in effect.
    public function testMixedProtocols2(): void
    {
        $http = new HttpClient('server', 9550);
        $http->set(['timeout' => 5]);
        $ok = $http->get('/');
        self::assertTrue($ok);
        self::assertSame(200, $http->statusCode);
        self::assertSame('Hello from the HTTP listener on port 9550.', trim((string) $http->body));

        $tcp = new TcpClient(SWOOLE_SOCK_TCP);
        $tcp->set(['timeout' => 5]);
        self::assertTrue($tcp->connect('server', 9551, 5));
        $request = "GET / HTTP/1.1\r\nHost: server\r\n\r\n";
        $tcp->send($request);
        $response = $tcp->recv();
        $tcp->close();
        self::assertSame($request, $response);
    }

    // One server process listens on both ports with per-port 'receive' callbacks; the response prefix proves
    // which port's callback handled the request.
    public function testMultiplePorts(): void
    {
        foreach ([9530, 9531] as $port) {
            $client = new TcpClient(SWOOLE_SOCK_TCP);
            $client->set(['timeout' => 5]);
            self::assertTrue($client->connect('server', $port, 5), "failed to connect to port {$port}");
            $client->send('hello');
            $response = $client->recv();
            $client->close();
            self::assertSame("port {$port}: hello" . PHP_EOL, $response);
        }
    }

    public function testRockPaperScissors(): void
    {
        $shapes = ['A' => 'Rock', 'B' => 'Paper', 'C' => 'Scissors'];
        $chan   = new Channel(3);

        foreach ($shapes as $name => $shape) {
            go(function () use ($name, $shape, $chan): void {
                $client = new HttpClient('server', 9801);
                $client->set(['timeout' => 10]);
                $client->post("/?name={$name}", ['shape' => $shape]);
                $chan->push((string) $client->body);
            });
        }

        /** @var list<string> $bodies */
        $bodies = [$chan->pop(), $chan->pop(), $chan->pop()];

        foreach ($shapes as $name => $shape) {
            $needle = "{$name}: {$shape}";
            $found  = false;
            foreach ($bodies as $body) {
                if (str_contains($body, $needle)) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, "no response body contained \"{$needle}\"; bodies: " . implode(' | ', $bodies));
        }
    }

    public function testHttp1Integrated(): void
    {
        foreach (['task', 'taskwait', 'taskWaitMulti', 'taskCo'] as $type) {
            $client = new HttpClient('server', 9502);
            $client->set(['timeout' => 5]);
            $client->get("/?type={$type}");
            self::assertSame(200, $client->statusCode, "?type={$type}");
        }
    }

    public function testWebsocketIntegrated(): void
    {
        $ws = new HttpClient('server', 9508);
        $ws->set(['timeout' => 5]);
        self::assertTrue($ws->upgrade('/'));
        $ws->push('Swoole');
        $frame = $ws->recv();
        self::assertInstanceOf(Frame::class, $frame);
        self::assertSame('Hello, Swoole', $frame->data);
    }
}
