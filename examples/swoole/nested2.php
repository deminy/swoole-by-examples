<?php
go(function () {
    go(function () {
        co::sleep(3);
        go(function () {
            co::sleep(2);
        });
    });
    co::sleep(1);
});

// This script takes about 5 seconds to finish.
