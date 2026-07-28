<?php

declare(strict_types=1);

namespace Tests\Client;

use Tests\Support\ExampleTestCase;

// examples/locks/lock-across-threads.php is intentionally NOT covered here — it requires a ZTS build of
// PHP/Swoole, which the `client` container (phpswoole/swoole:6.2-php8.4, non-ZTS) does not provide, and there is
// no access to a separate ZTS container or the Docker socket to start one from inside this container. Verify it
// manually per its own docblock: `docker run --rm -v "$(pwd):/var/www" -ti phpswoole/swoole:6.2-zts php ./examples/locks/lock-across-threads.php`.
class LocksTest extends ExampleTestCase
{
    public function testLockAcrossCoroutines(): void
    {
        $result = $this->runExample('locks/lock-across-coroutines.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }

    public function testLockAcrossProcesses(): void
    {
        $result = $this->runExample('locks/lock-across-processes.php');
        self::assertSame(0, $result['code'], $result['output']);
        self::assertSame('12345678', trim($result['output']));
    }
}
