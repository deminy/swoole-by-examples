<?php

declare(strict_types=1);

namespace Tests\Server;

use Tests\Support\ExampleTestCase;

// Must run from the `server` container: apcu-caching.php needs the APCu extension, which is installed only there.
class ServersTest extends ExampleTestCase
{
    // Self-driving: a user process sends 99 requests to the server concurrently, prints the summary of the per-worker
    // counters, then shuts the server down. How the requests are spread across the workers varies from run to run.
    public function testApcuCaching(): void
    {
        $result = $this->runExample('servers/apcu-caching.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertMatchesRegularExpression('/^counter_[0-2]: \d+$/m', $result['output']);
        self::assertStringContainsString('Total: 99', $result['output']);
    }
}
