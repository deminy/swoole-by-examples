#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use different types of callbacks when creating coroutines.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/coroutines/creation-2.php"
 *
 * @see https://www.php.net/manual/en/language.types.callable.php Callbacks / Callables
 */

use Swoole\Coroutine;

use function Swoole\Coroutine\run;

// Define a callback function.
function callbackFunction(int $i): void
{
    echo $i;
}

// Define a class with both static and non-static callback methods included.
class callbackClass
{
    public function callbackMethod(int $i): void
    {
        echo $i;
    }

    public static function staticCallbackMethod(int $i): void
    {
        echo $i;
    }
}

// An invokable class.
class InvokableClass
{
    public function __invoke(int $i): void
    {
        echo $i;
    }
}

run(function () {
    // Type 1: A simple callback function defined directly within the code block.
    Coroutine::create(function () {echo 1; });

    // Type 2: A simple callback function defined somewhere previously.
    Coroutine::create('callbackFunction', 2);

    // Type 3: Use object method call as callback.
    Coroutine::create([new callbackClass(), 'callbackMethod'], 3);

    // Type 4: Use static class method call as callback.
    Coroutine::create(['callbackClass', 'staticCallbackMethod'], 4);

    // Type 5: Use static class method call as callback.
    Coroutine::create('callbackClass::staticCallbackMethod', 5);

    // Type 6: Objects implementing __invoke can be used as callbacks.
    Coroutine::create(new InvokableClass(), 6);
});

echo PHP_EOL;
