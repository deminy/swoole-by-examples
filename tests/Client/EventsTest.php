<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class EventsTest extends ExampleTestCase
{
    public function testDefaultExitCondition(): void
    {
        $result = $this->runExample('events/default-exit-condition.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Two signal listeners are registered.', $result['output']);
        self::assertStringContainsString('the process now ends without any signal ever being handled', $result['output']);
        self::assertStringNotContainsString('Signal received', $result['output']);
    }

    public function testCustomizedExitCondition(): void
    {
        $result = $this->runExample('events/customized-exit-condition.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Signal received: ' . SIGTERM, $result['output']);
        self::assertStringContainsString('All signal listeners are unregistered; the process now exits.', $result['output']);
    }

    public function testWaitSignal(): void
    {
        $result = $this->runExample('events/wait-signal.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('The child process is waiting for a SIGUSR1 signal.', $result['output']);
        self::assertStringContainsString('Signal received: ' . SIGUSR1 . ' (SIGUSR1).', $result['output']);
        self::assertStringContainsString('Signal received before the 0.5-second timeout: false', $result['output']);
    }
}
