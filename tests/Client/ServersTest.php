<?php

declare(strict_types=1);

namespace Tests\Client;

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http2\Client as Http2Client;
use Swoole\Coroutine\Http\Client as HttpClient;
use Swoole\Http2\Request as Http2Request;
use Swoole\Http2\Response as Http2Response;
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

    // Not Supervisord-managed; self-driving (connects to itself, then watches the server side of the connection's
    // keepalive timer in /proc/net/tcp for about 5s, through the first keepalive probe).
    public function testKeepalive(): void
    {
        $result = $this->runExample('servers/keepalive.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Reply from the server: ping', $result['output']);
        self::assertSame(10, preg_match_all('/^The next keepalive probe will be sent in (\d\.\d) seconds\.$/m', $result['output'], $matches), $result['output']);
        $timesLeft = array_map(floatval(...), $matches[1]);

        // The countdown starts at the 3-second idle time. Once the first probe has been sent and answered, the timer is
        // set again, so the time left goes up at some point. (Where exactly the samples land varies a little.)
        self::assertGreaterThanOrEqual(2.5, $timesLeft[0], $result['output']);
        $wentUp = false;
        for ($i = 1, $n = count($timesLeft); $i < $n; $i++) {
            $wentUp = $wentUp || $timesLeft[$i] > $timesLeft[$i - 1];
        }
        self::assertTrue($wentUp, $result['output']);
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

    // Not Supervisord-managed; self-driving (a user process runs mosquitto_sub and mosquitto_pub against the broker, then
    // shuts it down). The Mosquitto command-line clients are installed only in the client container.
    public function testMqtt(): void
    {
        $result = $this->runExample('servers/mqtt.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('The subscriber received: Hello, MQTT', trim($result['output']));
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

    // Not Supervisord-managed; self-driving (starts an upstream HTTP server and the proxy, sends a request through the
    // proxy, then shuts both down).
    public function testProxy(): void
    {
        $result = $this->runExample('servers/proxy.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('Response through the proxy: HTTP 234, body: Hello from the upstream server!', trim($result['output']));
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

    // Not Supervisord-managed; self-driving (a WebSocket round trip, then the server runs for 2.5s while its two user
    // processes print their messages, then shutdown).
    public function testWebsocketIntegrated(): void
    {
        $result = $this->runExample('servers/websocket-integrated.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Reply from the WebSocket server: Hello, Swoole', $result['output']);
        self::assertGreaterThanOrEqual(2, substr_count($result['output'], 'Task processed.'), $result['output']);
        self::assertGreaterThanOrEqual(1, substr_count($result['output'], 'Cron job executed.'), $result['output']);
    }
}
