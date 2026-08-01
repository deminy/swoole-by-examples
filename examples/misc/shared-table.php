#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This example shows how to use class \Swoole\Table to share structured data between processes.
 *
 * Class \Swoole\Table implements a fixed-schema, in-memory table built on shared memory:
 *   - Memory is pre-allocated when method create() is called. Define the columns first, then call create(), and do
 *     both BEFORE child processes are forked (or before method \Swoole\Server::start() is called), so that all
 *     processes work on the same underlying shared memory.
 *   - Rows are keyed by strings, with fixed-type columns (TYPE_INT, TYPE_FLOAT, or TYPE_STRING).
 *   - A TYPE_STRING column has a fixed size in bytes; longer values assigned to it are truncated, with a warning
 *     logged.
 *   - Row operations are safe across processes, and methods incr() and decr() are atomic: multiple processes can
 *     update the same counter column concurrently without locks and without losing updates.
 *   - The class implements interfaces \Iterator and \Countable, so a table can be traversed with foreach and counted
 *     with method count().
 *
 * How to run this script:
 *     docker compose exec -t client bash -c "./misc/shared-table.php"
 */

use Swoole\Process;
use Swoole\Table;

// The table must be fully set up (columns defined and memory allocated via method create()) before child processes
// are forked. The table can hold at least 64 rows; extra rows are dropped once the table runs out of capacity.
$table = new Table(64);
$table->column('name', Table::TYPE_STRING, 8);
$table->column('score', Table::TYPE_FLOAT);
$table->column('visits', Table::TYPE_INT);
$table->create();

$table->set('player1', ['name' => 'Alice', 'score' => 9.5, 'visits' => 0]);
$table->set('player2', ['name' => 'Alexander', 'score' => 7.25, 'visits' => 6]);

// A TYPE_STRING column stores at most the number of bytes declared for it: the 9-character name "Alexander" is
// truncated to fit into the 8-byte column, and Swoole logs a warning when that happens (visible in the output of
// this script, right before anything else).
echo 'Name stored for player2: ', var_export($table->get('player2', 'name'), true), PHP_EOL; // 'Alexande'

// Methods incr() and decr() update a column atomically and return the new value.
echo 'Visits of player2 after decrementing by 2: ', $table->decr('player2', 'visits', 2), PHP_EOL; // 4

// Now two child processes increment the same counter column concurrently. Since the increments are atomic, no
// updates are lost, and the final value is always exactly 2,000. Compare this with a counter stored in a plain PHP
// variable, which is copied on fork: updates made in one process would be invisible to the others.
for ($i = 0; $i < 2; $i++) {
    $process = new Process(
        function () use ($table): void {
            for ($j = 0; $j < 1000; $j++) {
                $table->incr('player1', 'visits');
            }
        },
        false
    );
    $process->start();
}

// Reap both child processes in the parent to avoid leaving zombie processes behind.
for ($i = 0; $i < 2; $i++) {
    Process::wait();
}

echo 'Visits of player1 after two child processes each incremented the counter 1,000 times: ', var_export($table->get('player1', 'visits'), true), PHP_EOL; // 2000

// The parent process sees all the rows, including the updates made by the child processes.
echo 'Number of rows: ', $table->count(), PHP_EOL; // 2
foreach ($table as $key => $row) {
    echo "Row {$key}: ", json_encode($row, JSON_THROW_ON_ERROR), PHP_EOL; // @phpstan-ignore encapsedStringPart.nonString
}

$table->del('player2');
echo 'player2 still exists after deletion: ', var_export($table->exists('player2'), true), PHP_EOL; // false
echo 'Number of rows after deleting player2: ', $table->count(), PHP_EOL; // 1
