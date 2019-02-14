<?php
go(function() {
    echo "1";
    go(function () {
        echo "2";
        co::sleep(3);
        echo "3";
        go(function () {
            echo "4";
            co::sleep(2);
            echo "5";
        });
        echo "6";
    });
    echo "7";
    co::sleep(1);
    echo "8";
});
echo "9";

// Output: 127983465
// This script takes about 5 seconds to finish.
