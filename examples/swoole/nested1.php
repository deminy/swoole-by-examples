<?php
go(function() {
    co::writeFile("/tmp/swoole.tmp", rand());
    go(function () {
        $i = co::readFile("/tmp/swoole.tmp");
        go(function () use ($i) {
            echo $i, "\n";
        });
    });
});
