<?php
(function () {
    sleep(2);
    echo "1";
})();

(function () {
    sleep(1);
    echo "2";
})();

// Output: 12
// This script takes about 3 seconds to finish.
