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
 *     Name of the 1st object in the Context object of coroutine #1: co1_obj1
 *     Name of the 2nd object in the Context object of coroutine #2: co2_obj2
 *
 *     Coroutine #2 is exiting. The context object of it will be destroyed.
 *     Object "co2_obj2" is destroyed.
 *     Object "co2_obj1" is destroyed.
 *
 *     Coroutine #1 is exiting. The context object of it will be destroyed.
 *     Object "co1_obj2" is destroyed.
 *     Object "co1_obj1" is destroyed.
 */

use Swoole\Coroutine;

use function Swoole\Coroutine\run;

run(function () {
    $class = new class() {
        public string $name;

        public function __construct(string $name = '')
        {
            $this->name = $name;
        }

        public function __destruct()
        {
            if (!empty($this->name)) {
                echo 'Object "', $this->name, '" is destroyed.' . PHP_EOL;
            }
        }
    };

    // The Context object of a coroutine works the same as an \ArrayObject object.
    Coroutine::getContext()['co1_obj1']   = new $class('co1_obj1'); // @phpstan-ignore offsetAccess.nonOffsetAccessible
    $cid                                  = Coroutine::getCid();    // Coroutine::getCid() returns 1.
    Coroutine::getContext($cid)->co1_obj2 = new $class('co1_obj2'); // @phpstan-ignore property.nonObject

    Coroutine::create(function () use ($class) {
        // The Context object of a coroutine works the same as an \ArrayObject object.
        Coroutine::getContext()['co2_obj1']   = new $class('co2_obj1'); // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $cid                                  = Coroutine::getCid();    // Coroutine::getCid() returns 2.
        Coroutine::getContext($cid)->co2_obj2 = new $class('co2_obj2'); // @phpstan-ignore property.nonObject

        echo 'Name of the 1st object in the Context object of coroutine #1: ', Coroutine::getContext(1)['co1_obj1']->name, PHP_EOL; // @phpstan-ignore-line
        echo 'Name of the 2nd object in the Context object of coroutine #2: ', Coroutine::getContext(2)->co2_obj2->name, PHP_EOL; // @phpstan-ignore property.nonObject

        echo PHP_EOL, 'Coroutine #2 is exiting. The context object of it will be destroyed.', PHP_EOL;
    });

    echo PHP_EOL, 'Coroutine #1 is exiting. The context object of it will be destroyed.', PHP_EOL;
});
