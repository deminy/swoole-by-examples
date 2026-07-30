# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A collection of runnable, self-contained [Swoole](https://github.com/swoole/swoole-src) examples (`examples/`),
each demonstrating one specific Swoole feature or behavior. This is a teaching repository, not an application —
changes should keep individual examples minimal, focused on the one concept they demonstrate, and runnable in
isolation. Licensed under CC BY-NC-ND 4.0 (see `LICENSE.txt`).

No PHP/Swoole/Composer needs to be installed on the host — the only prerequisite is Docker with Compose v2 (the
`docker compose` subcommand). Everything runs via Docker Compose: two PHP containers (`server`, `client`) plus
three backing datastore services (`redis`, `mysql`, `postgresql`) that the database/Redis examples connect to,
with hostnames and credentials defined in `docker-compose.yml`'s environment blocks. Compose *pulls* the
pre-built images `deminy/swoole-by-examples:server-6.2` / `client-6.2` (both `phpswoole/swoole:6.2-php8.4` based,
from `dockerfiles/server/Dockerfile` and `dockerfiles/client/Dockerfile`) rather than building them — the images
are built and published by the `build_docker_images.yml` workflow, so to test a Dockerfile change locally,
`docker build` and retag it yourself (e.g. `docker build -t deminy/swoole-by-examples:server-6.2
dockerfiles/server`) before `docker compose up -d`.

## Development environment

```bash
docker compose up -d          # starts both containers; give it a few seconds for Supervisord-managed servers to bind
docker compose exec -ti server bash
docker compose exec -ti client bash
```

Both containers mount the whole repo root at `/var/www`, with `/var/www/examples` as the working directory (set
via `WORKDIR` in both Dockerfiles, so the run commands documented in the examples' docblocks work as-is). The
**client** container is where client-side/standalone scripts are normally run (the `redis`/`mysql`/`postgresql`
services are reachable from both PHP containers, which share the Compose default network);
the **server** container additionally runs 18 persistent, Supervisord-managed application servers (listed in
`docker-compose.yml`'s `AUTORELOAD_PROGRAMS` env var) that many client-side examples connect to.

Running a single example: each example's docblock documents its own exact run command (which container, any
required args), e.g. `docker compose exec -t client bash -c "./clients/http1.php"`. A few examples must run from
`server` specifically — either because they say so in their docblock, or because they depend on
container-local state (e.g. `pool/process-pool/client.php`'s Unix-socket connection only works from `server`,
since that socket file lives in `server`'s own filesystem). `examples/hooks/redis/predis.php` requires
`composer global require predis/predis=~3.0` inside the container first: the script deliberately loads Composer's
*global* autoloader (`$HOME/.composer/vendor/autoload.php`), so the project-level `composer install` — which also
installs predis, but only so PHPStan can resolve its symbols — does not satisfy it.
`examples/locks/lock-across-threads.php` needs a ZTS build of PHP/Swoole, which neither container image provides;
run it via `docker run --rm -v "$(pwd):/var/www" -ti phpswoole/swoole:6.2-zts php ./examples/locks/lock-across-threads.php`.

## Running tests

Every example is covered by a PHPUnit test suite (`tests/`), run with
[`deminy/counit`](https://github.com/deminy/counit) instead of plain PHPUnit so tests that sleep or wait on a
subprocess still run fast — most test methods run in their own Swoole coroutine automatically, so blocking test
bodies across different tests run concurrently. `vendor/` isn't committed; install dependencies before running
tests.

```bash
docker compose exec -T -w /var/www client composer install -n -q --no-progress   # once is enough — both containers share the same mount
docker compose exec -T -w /var/www client ./vendor/bin/counit --testsuite client
docker compose exec -T -w /var/www server ./vendor/bin/counit --testsuite server
```

Note the `-w /var/www`: `composer.json` lives at the repo root, while the containers' default working directory
is `/var/www/examples`.

Run a single test class or method with `--filter` (from `/var/www` inside a container):

```bash
./vendor/bin/counit --testsuite client --filter CspTest
./vendor/bin/counit --testsuite client --filter '::testDefer$'
```

Almost everything is in the `client` suite; only `tests/Server/PoolProcessTest.php` is in `server` (same reason
as `pool/process-pool/client.php` above — it needs `server`'s own filesystem). `csp/coroutines/benchmark.php` is
intentionally skipped (`self::markTestSkipped()` in `CspTest`, it creates 1,000,000 coroutines), and
`locks/lock-across-threads.php` is intentionally not covered at all (ZTS requirement, see `LocksTest`'s header
comment).

A handful of tests — ones covering examples that may hang forever by design (the deadlock demos, the
process-blocking `io/block-*` examples) or that depend on Swoole's preemptive scheduler actually firing
(`csp/scheduling/mixed.php`, `preemptive.php` and `non-preemptive.php` — all of `SchedulingTest`) — run under
PHPUnit's `#[RunInSeparateProcess]` attribute instead of
the default coroutine style, and call `ExampleTestCase::runIsolated()` instead of `runExample()`. Running many
concurrent `proc_open()`+coroutine operations at once was confirmed to be unreliable in this environment
(intermittent segfaults at scale, and the preemptive scheduler missing its window under concurrent load), so
these run one at a time in a genuinely separate, non-coroutine process instead. That trades speed for
reliability — the full `client` suite takes roughly a minute, dominated by these sequential tests, versus ~10-20s
if everything ran concurrently. See the docblocks on `Tests\Support\ExampleTestCase::runExample()` and
`runIsolated()` for the full reasoning (they're two genuinely different execution primitives — coroutine-based
polling with a shared concurrency semaphore vs. plain blocking `proc_open()`/`usleep()` — because
`#[RunInSeparateProcess]` test bodies run with no active Swoole coroutine context, where the coroutine APIs
`runExample()` relies on throw "API must be called in the coroutine").

`docker compose up -d` needs a few seconds before the persistent-server tests are reliable, and the
timeout-based tests can fail spuriously if the host is under heavy unrelated Docker load — worth knowing before
treating either as a real regression.

## Code quality

```bash
# Coding style (PHP-CS-Fixer, config in .php-cs-fixer.dist.php); needs `composer install` run first (see
# "Running tests" above)
docker compose exec -T -w /var/www client ./vendor/bin/php-cs-fixer fix --dry-run --show-progress=none
# drop --dry-run to actually apply fixes

# Static analysis (PHPStan level 9 over ./examples and ./tests); needs `composer install` run first (see
# "Running tests" above). PHPStan runs inside the client container so Swoole symbols resolve against the
# actual loaded extension, with predis and swoole/ide-helper coming from the project's dev dependencies.
docker compose exec -T -w /var/www client ./vendor/bin/phpstan analyse --no-progress --memory-limit 2G
```

Both are run by the `tests.yml` CI workflow on every push/PR (and manually via `workflow_dispatch`).
`phpstan.neon.dist` carries a per-file `ignoreErrors` list for cases where PHPStan's understanding of a Swoole
class's API lags the actual extension (e.g. it thinks `Swoole\Lock::lock()` takes no arguments, and doesn't know
`Swoole\Coroutine\Lock` or `Swoole\Thread` exist at all) — when adding a new
`ignoreErrors` entry, scope it to the specific file and message/identifier rather than broadening it, matching the
existing entries.

## Architecture

**`examples/`** — one runnable `.php` script per concept, grouped by topic: `csp/` (with `coroutines/`,
`deadlocks/` and `scheduling/`), `hooks/` (with `redis/`), `pool/` (with `database-pool/` and `process-pool/`),
`clients/`, `io/`, `locks/`, `misc/`, `servers/`, and `timer/`. Every script is meant to be
copy-paste-runnable and documents its own invocation in a docblock — keep that convention when adding examples.
Every new example must also come with a unit test, written the same way as the existing ones (see **`tests/`**
below): one test method per example, added to the topic's test class (or a new test class for a new topic),
normally driving the example through `runExample()` and asserting on its output.
Persistent, Supervisord-managed servers — mostly under `examples/servers/`, plus the three `pool-*` programs
whose scripts live in `examples/pool/process-pool/` — are wired up via
`dockerfiles/server/rootfilesystem/etc/supervisor/service.d/*.conf` and the `AUTORELOAD_PROGRAMS` env var in
`docker-compose.yml`; adding a new persistent-server example means adding both.

**`tests/`** mirrors `examples/`'s subdirectory structure: one test class per topic
(`tests/Client/CspTest.php`, `HooksTest.php`, `LocksTest.php`, `IoTest.php`, `MiscTest.php`, `TimerTest.php`,
`PoolTest.php`, `ClientsTest.php`, `DeadlocksTest.php`, `SchedulingTest.php`, `ServersTest.php`), one test method
per example. `tests/Support/ExampleTestCase.php` is the shared base every test class extends, providing:
- `runExample($path, $args, $timeout)` — the default; runs an example to completion via a coroutine-friendly
  `proc_open()`, capped at 8 concurrent in-flight calls via a shared `Channel` semaphore.
- `runIsolated($path, $timeout, $args)` — for `#[RunInSeparateProcess]`-marked test methods only (see "Running
  tests" above); same idea, plain blocking primitives instead of coroutine ones.

`tests/Client/ServersTest.php` covers the persistent Supervisord-managed servers directly, using Swoole's
coroutine HTTP/HTTP2/TCP/Redis clients to connect out to the `server` container (`heartbeat.php` and
`server-events.php` aren't Supervisord-managed — they're self-contained scripts that start and drive their own
server internally, so they're just run like any other example instead).

**CI** (`.github/workflows/`): `tests.yml` runs coding style checks, static analysis, and both counit test
suites on every push/PR, in a Compose environment whose server/client images it builds from `dockerfiles/`
itself (not the published ones — so image changes are exercised before ever being published).
`build_docker_images.yml` builds and publishes `deminy/swoole-by-examples:{server,client}-6.2` to Docker Hub
once `tests.yml` succeeds on `master` (`workflow_run` trigger; also runnable manually), gated to
`github.repository == 'deminy/swoole-by-examples'` (forks won't publish).

**Swoole version gotchas worth knowing before touching lock/process-related examples**: several APIs changed
between Swoole 6.0 and 6.1+, and this repo's examples/tests have already hit and fixed each of these once —
`Swoole\Coroutine\Lock::trylock()`, `Swoole\Thread\Lock::trylock()`, and `Swoole\Lock::trylock()` were all removed
(replaced by `lock(LOCK_EX | LOCK_NB)` or `lock($operation, $timeout)`); `Swoole\Process::start()` cannot be
called from inside a coroutine; `Swoole\Coroutine\System::exec()` is unreliable when run concurrently alongside
raw `proc_open()` children; `Swoole\Coroutine\System::waitPid($pid, 0.0)` blocks indefinitely rather than doing an
instant non-blocking check the way `0.0` typically means elsewhere.
