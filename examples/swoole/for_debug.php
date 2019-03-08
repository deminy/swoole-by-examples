<?php

Swoole\Runtime::enableCoroutine();

for ($i = 1; $i <= 2000; $i++) {
    go(function () {
        sleep(1);
    });
}

// Print out message "2000 active coroutines when reaching the end of the PHP script.".
echo count(Swoole\Coroutine::listCoroutines()), " active coroutines when reaching the end of the PHP script.\n";
