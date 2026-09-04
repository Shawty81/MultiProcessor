# MultiProcessor

Process a large dataset in parallel by handing chunks of it to forked child processes.

The parent keeps a pool of children busy, hands each one a chunk of your data, and reaps
them as they finish. Because every chunk is processed in a process of its own, a fatal
error, a segfault or an out of memory kill in one chunk cannot take the run down: the
parent notices the child died, retries the chunk, and carries on with the rest.

This is a command line tool. It forks with `pcntl_fork()` and signals with `posix_kill()`,
so it needs `ext-pcntl` and `ext-posix` and must not be run under a web server, PHP-FPM or
any other environment where the process is not yours to fork.

## Requirements

- PHP 8.4 or newer
- `ext-pcntl` and `ext-posix`
- A CLI SAPI

## Installation

```
composer require shawty81/multiprocessor
```

## Quick start

Two interfaces make up everything you write: an iterator that hands out chunks of work,
and a child processor that does the work for one chunk. `ArrayIterator` ships with the
library, so this runs as it stands.

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use MultiProcessor\ChildProcessor\ChildProcessorInterface;
use MultiProcessor\Iterator\ArrayIterator;
use MultiProcessor\Log\CommandLineLogger;
use MultiProcessor\MultiProcessor;
use MultiProcessor\Queue\Chunk;
use MultiProcessor\Settings;
use Psr\Log\LoggerAwareTrait;

final class SquareEverything implements ChildProcessorInterface
{
    use LoggerAwareTrait;

    #[Override]
    public function init(): void {}

    #[Override]
    public function process(Chunk $chunk): void
    {
        foreach ($chunk->data as $number) {
            $this->logger?->info('pid {pid}: {number} squared is {result}', [
                'pid' => posix_getpid(),
                'number' => $number,
                'result' => $number ** 2,
            ]);
        }
    }

    #[Override]
    public function finish(): void {}
}

$logger = new CommandLineLogger();

$iterator = new ArrayIterator();
$iterator->setArray(range(1, 20));

$childProcessor = new SquareEverything();
$childProcessor->setLogger($logger);

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: $logger,
    maxChildren: 4,
    chunkSize: 5,
);

new MultiProcessor($settings)->run();
```

Run it and you get four children working on five numbers each, followed by a summary:

```
15:04:05 [I]  Starting MultiProcessor
15:04:05 [I]  Parent pid: 4711
15:04:05 [I]  Chunks to process: 4
15:04:05 [I]
15:04:05 [I]  pid 4712: 1 squared is 1
...
15:04:05 [I]  MultiProcessor done!
15:04:05 [I]
15:04:05 [I]  Total time spent: 0 hours, 0 minutes and 0 seconds
15:04:05 [I]  Chunks handed to a child: 4
```

## The two interfaces you implement

### `IteratorInterface`

Lives entirely in the parent, except for `dropConnections()`. It is the single source of
work: the parent asks it for a chunk, forks a child for it, and asks for the next one.

| Method | Runs in | What it is for |
| --- | --- | --- |
| `init(): void` | parent | Called once before any forking. Open your connections, seek to the start of your file, prepare your query. |
| `getChunk(int $size): Chunk` | parent | Return the next `$size` records wrapped in a `Chunk`. Return a `Chunk` with an empty `data` array to say there is nothing left; that is what ends the run. |
| `getNumberOfChunks(int $chunkSize): int` | parent | Called once, after `init()`. The number is logged so a run can be followed. Nothing depends on it being exact, so it is fine to estimate it, or to return `0` when counting would be expensive. |
| `dropConnections(): void` | child | Called in the freshly forked child, before the chunk is processed. See below. |
| `finish(): void` | parent | Called once after the last chunk has been processed. Close what `init()` opened. |

`dropConnections()` exists because a fork copies the process, file descriptors and all. A
database handle that was open when the fork happened is now referred to by two processes
that both think they own it, and they will interleave on the same socket: results arrive
at the wrong process, and the connection is torn down for both when either one closes it.
The fix is for the child to throw the inherited handle away and, if it needs one, connect
again for itself. That is what this method is the place for.

```php
<?php

declare(strict_types=1);

namespace App\Import;

use MultiProcessor\Iterator\IteratorInterface;
use MultiProcessor\Queue\Chunk;
use Override;
use PDO;

final class UnprocessedOrderIterator implements IteratorInterface
{
    private ?PDO $connection = null;
    private int $lastId = 0;

    #[Override]
    public function init(): void
    {
        $this->connection = new PDO('mysql:host=localhost;dbname=shop', 'user', 'secret');
    }

    #[Override]
    public function getChunk(int $size): Chunk
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM orders WHERE processed = 0 AND id > :lastId ORDER BY id LIMIT :size'
        );
        $statement->bindValue('lastId', $this->lastId, PDO::PARAM_INT);
        $statement->bindValue('size', $size, PDO::PARAM_INT);
        $statement->execute();

        $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        if ($ids !== []) {
            $this->lastId = (int) $ids[array_key_last($ids)];
        }

        return new Chunk($ids);
    }

    #[Override]
    public function getNumberOfChunks(int $chunkSize): int
    {
        $total = (int) $this->connection->query('SELECT COUNT(*) FROM orders WHERE processed = 0')->fetchColumn();

        return (int) ceil($total / $chunkSize);
    }

    #[Override]
    public function dropConnections(): void
    {
        // The child inherited the parent's socket. Let go of it rather than share it.
        $this->connection = null;
    }

    #[Override]
    public function finish(): void
    {
        $this->connection = null;
    }
}
```

### `ChildProcessorInterface`

| Method | Runs in | What it is for |
| --- | --- | --- |
| `init(): void` | parent | Called once before any forking, alongside the iterator's `init()`. |
| `process(Chunk $chunk): void` | child | Does the actual work for one chunk. Anything it opens belongs to that child alone and disappears with it. |
| `finish(): void` | parent | Called once after the last chunk has been processed. |

`process()` should return when it is done and throw when it cannot finish. Do not call
`exit()` yourself: the library gives the child its exit status, and it uses that status to
tell the parent whether the chunk succeeded.

### `Chunk`

A `final readonly` value object carrying one unit of work.

| Property | Type | What it is |
| --- | --- | --- |
| `data` | `mixed[]` | The records this chunk is made of, exactly as your iterator returned them. |
| `retries` | `int` | How many times this chunk has been handed to a child again after a failure. `0` on the first attempt. |

## Settings

`Settings` is a `final readonly` value object. Build it with named arguments; only the
first two have no default.

```php
use MultiProcessor\Settings;

$settings = new Settings(
    iterator: $iterator,
    childProcessor: $childProcessor,
    logger: $logger,
    maxChildren: 8,
    chunkSize: 250,
    retryOnFatal: true,
    maxRetries: 2,
    exitOnFatal: false,
);
```

| Setting | Type | Default | What it does |
| --- | --- | --- | --- |
| `iterator` | `IteratorInterface` | required | Where the chunks come from. |
| `childProcessor` | `ChildProcessorInterface` | required | What a child does with a chunk. |
| `logger` | `?LoggerInterface` | `null` | The PSR-3 logger the MultiProcessor itself reports to. With `null` it stays silent. |
| `maxChildren` | `int` | `1` | How many children may run at once. The default of `1` means no parallelism at all, so this is the setting to raise first. Must be at least `1`. |
| `chunkSize` | `int` | `10` | How many records one child gets. Passed straight to `getChunk()`. Must be at least `1`. |
| `retryOnFatal` | `bool` | `true` | Whether a chunk whose child died is handed out again. |
| `maxRetries` | `int` | `1` | How many extra attempts a chunk gets. The default gives every chunk two attempts in total. Must be `0` or more. |
| `exitOnFatal` | `bool` | `false` | Whether the first dead child aborts the whole run. |

A value outside those bounds throws `InvalidSettingsException` from the constructor, so a
misconfigured run fails before anything is forked.

## What happens when a child fails

A child fails in one of two ways, and the parent treats them the same.

- **It threw.** The exception never reaches the parent, because it happened in another
  process. The child catches it, logs its class, message and stack trace, and exits with
  status `255`. The parent logs `Child (pid: ...) exited with an error.`
- **It was killed.** A segfault, an out of memory kill or a `kill -9` leaves the child no
  chance to report anything. The parent sees the signal in the wait status and logs
  `Child (pid: ...) was killed by signal 9.`

Either way the chunk that child was holding is then handled like this:

1. If `exitOnFatal` is `true`, the run stops immediately: every remaining child is killed
   with `SIGKILL` and the parent exits. `finish()` is not called and no summary is logged.
2. Otherwise, if `retryOnFatal` is `false`, the chunk is dropped and the run continues.
3. Otherwise, if the chunk has already been retried `maxRetries` times, the parent logs
   `Chunk from Child (pid: ...) failed N times, giving up on it.`, drops it, and continues.
4. Otherwise the chunk goes back on the queue with its `retries` count raised by one, and
   is handed to the next free child.

Retried chunks are taken from the queue before new ones are read from the iterator, so a
failure is retried promptly rather than at the end of the run.

Note the order: `exitOnFatal` is checked first, so setting it to `true` disables retrying
whatever `retryOnFatal` says.

A run that a child cannot be forked for at all is not recoverable, and the parent throws
`ForkFailedException`.

## Signals

The parent installs handlers for `SIGINT`, `SIGTERM` and `SIGCHLD`.

`SIGINT` (Ctrl-C) or `SIGTERM` on the parent starts an orderly shutdown: it logs what it
is doing, sends `SIGKILL` to every child still in the pool, and exits. It does not wait
for children to finish their chunk, and it does not call `finish()` or log a summary. The
handlers are installed so that they interrupt the blocking wait the parent spends nearly
all of its time in, so the shutdown happens at once rather than when a child next exits.

Children inherit those handlers across the fork but have no business shutting the run
down on the parent's behalf. So the first thing a child does with an inherited signal is
put the default handler back and raise the signal on itself again, which leaves it dying
exactly as it would have without the fork. A child of your own that you fork inside
`process()` is unaffected by any of this.

## Logging

Anything implementing PSR-3's `LoggerInterface` will do. Pass it as the `logger` setting,
or set it afterwards, since `MultiProcessor` implements `LoggerAwareInterface`:

```php
use MultiProcessor\MultiProcessor;

$multiProcessor = new MultiProcessor($settings);
$multiProcessor->setLogger($logger);
$multiProcessor->run();
```

That logger is the MultiProcessor's own. Your iterator and child processor log through
whatever you give them; the quick start passes the same instance to both.

`CommandLineLogger` is bundled for when you just want to see the run on your terminal. It
writes one line per message, prefixed with the time and the first letter of the level, and
fills in the PSR-3 `{placeholders}` from the context:

```
15:04:05 [I]  Parent pid: 4711
15:04:05 [A]  Child (pid: 4712) exited with an error.
```

## Exceptions

Every exception this library throws implements
`MultiProcessor\Exception\ExceptionInterface`, so one `catch` covers all of it:

```php
use MultiProcessor\Exception\ExceptionInterface;

try {
    new MultiProcessor($settings)->run();
} catch (ExceptionInterface $exception) {
    // Anything MultiProcessor itself failed on.
}
```

| Exception | Extends | Thrown when |
| --- | --- | --- |
| `InvalidSettingsException` | `InvalidArgumentException` | `Settings` is given a `chunkSize` or `maxChildren` below `1`, or a negative `maxRetries`. |
| `ForkFailedException` | `RuntimeException` | `pcntl_fork()` failed, so no child could be started. |

Exceptions your own code throws are not caught by the library in the parent. In a child
they are caught, logged and turned into a failed chunk, as described above.

## License

Apache-2.0. See [LICENSE](LICENSE).
