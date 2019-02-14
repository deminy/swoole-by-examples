<?php

go(function () {
    echo "1";
    defer(function () {
        echo "6";
    });

    echo "2";
    defer(function () {
        echo "5";
    });

    echo "3";
    co::sleep(1);
    echo "4";
});

// Output: 123456
// This script takes about 1 second to finish.
