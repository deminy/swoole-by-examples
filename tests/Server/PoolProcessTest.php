<?php

declare(strict_types=1);

namespace Tests\Server;

use Tests\Support\ExampleTestCase;

class PoolProcessTest extends ExampleTestCase
{
    /**
     * Exercises the pool-msgqueue, pool-tcp-socket, and pool-unix-socket persistent servers (Supervisord-managed
     * in this same `server` container). Must run from `server`, not `client`: confirmed by testing that the
     * Unix-socket sub-check fails from `client` because /var/run/pool-unix-socket.sock only exists in `server`'s
     * filesystem (no shared volume for it), while running the same script from `server` succeeds end to end. Its
     * own docblock documents running it from `server` too.
     */
    public function testProcessPoolClient(): void
    {
        $result = $this->runExample('pool/process-pool/client.php');
        self::assertSame(0, $result['code'], $result['output']);
    }
}
