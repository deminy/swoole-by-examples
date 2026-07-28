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
}
