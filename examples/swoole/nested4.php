<?php
go(function() {
    echo "1";
    go(function () {
        echo "2";
        co::sleep(3);
        echo "6";
        go(function () {
            echo "7";
            co::sleep(2);
            echo "9";
        });
        echo "8";
    });
    echo "3";
    co::sleep(1);
    echo "5";
});
echo "4";

// Output: 123456789
// This script takes about 5 seconds to finish.
