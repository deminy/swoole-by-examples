<?php
go(function () {
    co::sleep(2);
    echo "1";
});

go(function () {
    co::sleep(1);
    echo "2";
});

// Output: 21
// This script takes about 2 seconds to finish.
