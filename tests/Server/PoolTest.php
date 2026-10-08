<?php

declare(strict_types=1);

namespace Tests\Server;

use Tests\Support\ExampleTestCase;

// Must run from the `server` container: pool-msgqueue.php needs PHP extension "sysvmsg", which is installed only there.
class PoolTest extends ExampleTestCase
{
    public function testProcessPoolMsgqueue(): void
    {
        $result = $this->runExample('pool/process-pool/pool-msgqueue.php');
        self::assertSame(0, $result['code'], $result['output']);
        for ($i = 1; $i <= 3; $i++) {
            self::assertMatchesRegularExpression("/Process #[12] received message \"Message #{$i}\"\\./", $result['output']);
        }
    }
}
