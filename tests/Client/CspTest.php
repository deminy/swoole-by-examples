<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

class CspTest extends ExampleTestCase
{
    public function testBarrier(): void
    {
        $result = $this->runExample('csp/barrier.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testChannel(): void
    {
        $result = $this->runExample('csp/channel.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testContext(): void
    {
        $result = $this->runExample('csp/context.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testDefer(): void
    {
        $result = $this->runExample('csp/defer.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }

    public function testWaitGroup(): void
    {
        $result = $this->runExample('csp/waitgroup.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    // csp/coroutines/benchmark.php: creates 1,000,000 coroutines; its own docblock warns it needs more CPU/memory
    // than a laptop provides. Skipped rather than run.
    public function testBenchmarkIsSkipped(): void
    {
        self::markTestSkipped('csp/coroutines/benchmark.php creates 1,000,000 coroutines and is too heavy to run here.');
    }

    public function testCoroutinesCreationSyntaxVariants(): void
    {
        $result = $this->runExample('csp/coroutines/creation-syntax-variants.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testCoroutinesCreationCallbackTypes(): void
    {
        $result = $this->runExample('csp/coroutines/creation-callback-types.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testCoroutinesEnableAndDisable(): void
    {
        $result = $this->runExample('csp/coroutines/enable-and-disable.php');
        self::assertSame(0, $result['code'], $result['output']);
        $expected = implode(PHP_EOL, [
            'Hook flags in a fresh process: 0',
            'Hook flags inside run() match SWOOLE_HOOK_ALL: true',
            '1212',
            'Hook flags persist after run() returns: true',
            'Hook flags after Runtime::setHookFlags(0): 0',
            '1122',
            'Hook flags SWOOLE_HOOK_TCP | SWOOLE_HOOK_FILE include the sleep hook: false',
            '1122',
            'Hook flags SWOOLE_HOOK_SLEEP include the sleep hook: true',
            '1212',
            'Hook flags after Runtime::enableCoroutine() match SWOOLE_HOOK_ALL: true',
            '1212',
        ]);
        self::assertSame($expected, trim($result['output']));
    }

    public function testCoroutinesExit(): void
    {
        $result = $this->runExample('csp/coroutines/exit.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testCoroutinesFor(): void
    {
        $result = $this->runExample('csp/coroutines/for.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testCoroutinesNested(): void
    {
        $result = $this->runExample('csp/coroutines/nested.php');
        self::assertSame(0, $result['code'], $result['output']);
    }

    public function testCoroutinesNestedDebug(): void
    {
        $result = $this->runExample('csp/coroutines/nested-debug.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('123456789', trim($result['output']));
    }

    public function testCoroutinesYieldAndResume(): void
    {
        $result = $this->runExample('csp/coroutines/yield-and-resume.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }
}
