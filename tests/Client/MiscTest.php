<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class MiscTest extends ExampleTestCase
{
    public function testAtomicCounterSigned64Bit(): void
    {
        $result = $this->runExample('misc/atomic-counter-signed-64-bit.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testAtomicCounterUnsigned32Bit(): void
    {
        $result = $this->runExample('misc/atomic-counter-unsigned-32-bit.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testMulticast(): void
    {
        $result = $this->runExample('misc/multicast.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('Datagram received through the multicast group: Hello, multicast group 224.10.20.30!', $result['output']);
    }

    public function testWaitAndWakeupProcesses(): void
    {
        $result = $this->runExample('misc/wait-and-wakeup-processes.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertStringContainsString('[consumer] Woken up by the producer; shared value is back to 0.', $result['output']);
        self::assertStringContainsString('[parent] Both child processes have exited.', $result['output']);
    }
}
