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

    // Not Supervisord-managed; like server-events.php above, it drives itself (schedules its own HTTP request
    // internally and shuts itself down once its demonstration work completes) and finishes in ~1.2s.
    public function testEnableCoroutine(): void
    {
        $result = $this->runExample('servers/enable-coroutine.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('"onWorkerStart" in the event worker runs in a coroutine: no', $result['output']);
        self::assertStringContainsString('"onWorkerStart" in the task worker runs in a coroutine: yes', $result['output']);
        self::assertStringContainsString('Runtime hooks are applied in workers even with enable_coroutine off: true', $result['output']);
        self::assertStringContainsString('"onRequest" runs in a coroutine: no', $result['output']);
        self::assertStringContainsString('Coroutines can still be created manually in "onRequest": yes', $result['output']);
        self::assertStringContainsString('Three coroutines slept for 1 second each; time spent in total: about 1 second (the coroutines do not block each other).', $result['output']);
        self::assertStringContainsString('"onTask" runs in a coroutine: yes', $result['output']);
        self::assertStringContainsString('Task data is delivered as a Swoole\Server\Task object.', $result['output']);
    }

    // Not Supervisord-managed; a coroutine-style server that the script starts, sends three concurrent requests to, and
    // shuts down itself, in about 1 second.
    public function testCoroutineHttpServer(): void
    {
        $result = $this->runExample('servers/coroutine-http-server.php');
        self::assertSame(0, $result['code'], $result['output']);
        for ($i = 1; $i <= 3; $i++) {
            self::assertMatchesRegularExpression("/Response to request #{$i}: Hello from coroutine #\\d+!/", $result['output']);
        }
        self::assertStringContainsString('Three requests, each taking 1 second, finished in about 1 second(s) in total.', $result['output']);
    }

    // Not Supervisord-managed; self-driving (creates its own self-signed certificate, makes two HTTPS requests to
    // itself, and shuts itself down).
    public function testHttps(): void
    {
        $result = $this->runExample('servers/https.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Client #1 (does not trust the certificate): request failed - the TLS handshake was rejected', $result['output']);
        self::assertStringContainsString('Client #2 (trusts the certificate): HTTP 200, body: Hello over HTTPS!', $result['output']);
    }

    // Not Supervisord-managed; self-driving (a user process makes requests, updates the handler file, reloads the
    // server, and shuts it down).
    public function testHotReload(): void
    {
        $result = $this->runExample('servers/hot-reload.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertMatchesRegularExpression('/Before the reload: Hello from version 1 of the code \(worker process (\d+)\)/', $result['output']);
        self::assertMatchesRegularExpression('/After the reload:  Hello from version 2 of the code \(worker process (\d+)\)/', $result['output']);
        // The response after the reload comes from a new worker process.
        preg_match_all('/\(worker process (\d+)\)/', $result['output'], $matches);
        self::assertCount(2, $matches[1]);
        self::assertNotSame($matches[1][0], $matches[1][1]);
    }

    // Not Supervisord-managed; self-driving (worker #0 connects three WebSocket clients to the server, and shuts the
    // server down once the broadcast has been received).
    public function testWebsocketBroadcast(): void
    {
        $result = $this->runExample('servers/websocket-broadcast.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('broadcast to 3 connections.', $result['output']);
        foreach (['alice', 'bob', 'carol'] as $name) {
            self::assertStringContainsString("Client {$name} received: Broadcast: Hello, everyone! (from alice)", $result['output']);
        }
    }

    // Not Supervisord-managed; self-driving (6 concurrent requests to itself, then shutdown) and finishes in about 2s.
    public function testDdosProtection(): void
    {
        $result = $this->runExample('servers/ddos-protection.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame(6, preg_match_all('/^Response "OK \(connection #\d\)" received after \d\.\d seconds\.$/m', $result['output']));
        self::assertStringContainsString('Requests answered right away: 4; requests delayed by about 2 seconds: 2', $result['output']);
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

    // Not Supervisord-managed; self-driving (a user process talks to the server through phpredis, then shuts it down).
    public function testRedisServer(): void
    {
        $result = $this->runExample('servers/redis.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame(
            implode(PHP_EOL, ['SET foo bar: true', "GET foo: 'bar'", 'GET missing-key: false (the key does not exist)']),
            trim($result['output'])
        );
    }

    // Not Supervisord-managed; self-driving (streams 20 events to itself in about 2s, then shutdown).
    public function testHttp1Sse(): void
    {
        $result = $this->runExample('servers/http1-sse.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame(20, preg_match_all('/^data: \d{2} +\(received after \d+\.\d seconds\)$/m', $result['output']));
        self::assertStringContainsString('data: 01 ', $result['output']);
        self::assertStringContainsString('data: 20 ', $result['output']);
        self::assertStringContainsString('Content-Type of the response: text/event-stream; charset=utf-8', $result['output']);
    }

    // A publish/subscribe round trip against the minimal MQTT broker, using hand-crafted MQTT 3.1.1 packets
    // over a raw TCP connection (so the test does not depend on any MQTT client library or tool).
    public function testMqtt(): void
    {
        $mqttString    = static fn (string $s): string => pack('n', strlen($s)) . $s;
        $connectPacket = static function (string $clientId) use ($mqttString): string {
            $body = $mqttString('MQTT') . "\x04\x02\x00\x3c" . $mqttString($clientId);
            return "\x10" . chr(strlen($body)) . $body;
        };

        $newConnection = static function (string $clientId) use ($connectPacket): TcpClient {
            $client = new TcpClient(SWOOLE_SOCK_TCP);
            $client->set(['open_mqtt_protocol' => true, 'timeout' => 5]);
            self::assertTrue($client->connect('server', 9514, 5));
            $client->send($connectPacket($clientId));
            self::assertSame("\x20\x02\x00\x00", $client->recv(), 'expected a CONNACK packet'); // CONNACK, connection accepted.
            return $client;
        };

        $subscriber = $newConnection('phpunit-sub');
        $subscribeBody = pack('n', 1) . $mqttString('test/topic') . "\x00"; // Packet #1, topic "test/topic", QoS 0.
        $subscriber->send("\x82" . chr(strlen($subscribeBody)) . $subscribeBody);
        self::assertSame("\x90\x03\x00\x01\x00", $subscriber->recv(), 'expected a SUBACK packet');

        $publisher   = $newConnection('phpunit-pub');
        $publishBody = $mqttString('test/topic') . 'Hello, MQTT';
        $publisher->send("\x30" . chr(strlen($publishBody)) . $publishBody);

        $forwarded = $subscriber->recv();
        self::assertStringContainsString('test/topic', $forwarded);
        self::assertStringContainsString('Hello, MQTT', $forwarded);

        $subscriber->send("\xc0\x00"); // PINGREQ.
        self::assertSame("\xd0\x00", $subscriber->recv(), 'expected a PINGRESP packet');

        $subscriber->close();
        $publisher->close();
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

    // Not Supervisord-managed; self-driving (shuts itself down after 2s, interrupting the cron job's 19-second wait).
    public function testInterruptibleSleep(): void
    {
        $result = $this->runExample('servers/interruptible-sleep.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('[INTERRUPTIBLE-SLEEP] Simulating cron job execution. (case 1)', $result['output']);
        self::assertMatchesRegularExpression('/\[INTERRUPTIBLE-SLEEP\] Simulating cron job execution\. \(case 2, after [23] seconds\)/', $result['output']);
        self::assertStringContainsString('[INTERRUPTIBLE-SLEEP] The cron job has exited.', $result['output']);
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

    // Not Supervisord-managed; self-driving (talks to itself over HTTP/1, HTTP/2, and WebSocket, then shutdown).
    public function testMixedProtocolsSamePort(): void
    {
        $result = $this->runExample('servers/mixed-protocols-same-port.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame(
            implode(PHP_EOL, ['HTTP/1 response: Hello, HTTP/1', 'HTTP/2 response: Hello, HTTP/2', 'WebSocket message: Hello, WebSocket']),
            trim($result['output'])
        );
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

    // Not Supervisord-managed; self-driving. The primary port speaks HTTP, while the additional port has the inherited
    // HTTP protocol turned off, so it echoes an HTTP request back as raw bytes instead of parsing it.
    public function testMixedProtocolsPerPort(): void
    {
        $result = $this->runExample('servers/mixed-protocols-per-port.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame(
            implode(PHP_EOL, [
                'Reply from the primary (HTTP) port: HTTP 200, body: Hello from the HTTP listener.',
                'Reply from the additional (raw TCP) port: "GET / HTTP/1.1\\r\\nHost: localhost\\r\\n\\r\\n"',
            ]),
            trim($result['output'])
        );
    }

    // Not Supervisord-managed; self-driving (sends "hello" to each of its two ports, then shutdown).
    public function testMultiplePorts(): void
    {
        $result = $this->runExample('servers/multiple-ports.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame(
            implode(PHP_EOL, [
                'The main port replied: [callback of the main port] hello',
                'The additional port replied: [callback of the additional port] hello',
            ]),
            trim($result['output'])
        );
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
