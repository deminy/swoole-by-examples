<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class TimerTest extends ExampleTestCase
{
    public function testCoroutineStyle(): void
    {
        $result = $this->runExample('timer/coroutine-style.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testTimer(): void
    {
        $result = $this->runExample('timer/timer.php');
        self::assertSame(0, $result['code'], $result['output']);
    }
}
