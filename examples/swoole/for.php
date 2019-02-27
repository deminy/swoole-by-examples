<?php

Swoole\Runtime::enableCoroutine();

for ($i = 1; $i <= 2000; $i++) {
    go(function() {
        sleep(1);
    });
}

// This script takes about 1 second to finish.
// Without coroutine enabled in line 3, this script takes about 2,000 seconds to finish.
