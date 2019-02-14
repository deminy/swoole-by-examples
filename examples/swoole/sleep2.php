<?php
go(function () {
    echo "1";
    co::sleep(2);
    echo "6";
});
echo "2";
go(function () {
    echo "3";
    co::sleep(1);
    echo "5";
});
echo "4";

// Output: 123456
