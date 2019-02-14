<?php

Swoole\Runtime::enableCoroutine();

for ($i = 1; $i <= 2000; $i++) {
    go(function() {
        sleep(1);
    });
}

// This script takes about 1 second to finish.
