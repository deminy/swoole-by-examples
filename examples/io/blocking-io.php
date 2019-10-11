#!/usr/bin/env php
<?php
/**
 * How to run this script:
 *     docker exec -t  $(docker ps -qf "name=server") bash -c "time ./io/blocking-io.php"
 *
 * This script takes about 3 seconds to finish, and prints out "12".
 *
 * Here the PHP function sleep() is used to simulate blocking I/O. The non-blocking version takes about 2 seconds to
 * finish, as you can see in script "non-blocking-io.php".
 */

(function () {
    sleep(2);
    echo "1";
})();

(function () {
    sleep(1);
    echo "2";
})();
