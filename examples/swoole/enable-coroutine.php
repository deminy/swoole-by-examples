<?php
Swoole\Runtime::enableCoroutine();
function func1()
{
    sleep(2);
    echo __FUNCTION__, "\n";
}

function func2()
{
    sleep(1);
    echo __FUNCTION__, "\n";
}

go('func1');
go('func2');
