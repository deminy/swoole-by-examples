#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use Context objects in coroutines.
 *
 * The Context object of a coroutine is a key-value storage. It is used to store custom data for the coroutine.
 *
 * Each coroutine has a unique Context object automatically associated with it. The Context object of a coroutine can be
 * accessed by calling method `\Swoole\Coroutine::getContext()`; the object can be accessed from anywhere of current
 * process, as long as the associated coroutine still exists.
 *
 * The Context object will be automatically destroyed when the coroutine finishes execution and gets destroyed.
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./csp/context.php"
 *
 * The output should look like the following:
 *     Name of the object in the Context object of coroutine #1: co1_obj
 *     Name of the object in the Context object of coroutine #2: co2_obj
 *
 *     Coroutine #2 is exiting. The context object of it will be destroyed.
 *     Object "co2_obj" is destroyed.
 *
 *     Coroutine #1 is exiting. The context object of it will be destroyed.
 *     Object "co1_obj" is destroyed.
 */

use Swoole\Coroutine;

use function Swoole\Coroutine\run;

run(function (): void {
    $class = new class {
        public function __construct(public string $name = '')
        {
        }

        public function __destruct()
        {
            if (!empty($this->name)) {
                echo 'Object "', $this->name, '" is destroyed.' . PHP_EOL;
            }
        }
    };

    // Prior to PHP 8.2, items in the Context object of a coroutine could be set or accessed using dynamic properties,
    // e.g.,
    //   Coroutine::getContext($cid)->foo = 'bar';
    // Starting with PHP 8.2, dynamic properties are deprecated, so we use array-style access instead.

    // The Context object of a coroutine works the same as an \ArrayObject object.
    Coroutine::getContext()['co1_obj'] = new $class('co1_obj'); // @phpstan-ignore offsetAccess.nonOffsetAccessible
    $cid                               = Coroutine::getCid();   // Coroutine::getCid() returns 1.

    Coroutine::create(function () use ($class): void {
        // The Context object of a coroutine works the same as an \ArrayObject object.
        Coroutine::getContext()['co2_obj'] = new $class('co2_obj'); // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $cid                               = Coroutine::getCid();   // Coroutine::getCid() returns 2.

        echo 'Name of the object in the Context object of coroutine #1: ', Coroutine::getContext(1)['co1_obj']->name, PHP_EOL; // @phpstan-ignore offsetAccess.nonOffsetAccessible,property.nonObject
        echo 'Name of the object in the Context object of coroutine #2: ', Coroutine::getContext(2)['co2_obj']->name, PHP_EOL; // @phpstan-ignore offsetAccess.nonOffsetAccessible,property.nonObject

        echo PHP_EOL, 'Coroutine #2 is exiting. The context object of it will be destroyed.', PHP_EOL;
    });

    echo PHP_EOL, 'Coroutine #1 is exiting. The context object of it will be destroyed.', PHP_EOL;
});
