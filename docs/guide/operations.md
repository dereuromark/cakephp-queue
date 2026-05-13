# Operations

A field guide for running the queue in production. This is not a tutorial — it's a checklist of things that bite, with the configuration knob or admin-UI lever next to each one.

## Sizing workers

The right number of concurrent workers is bounded by:

- the slowest task you run (long-running tasks block the worker slot they occupy until completion);
- the database connection pool (each worker holds one connection);
- the I/O / CPU profile of your tasks (mailer-heavy: I/O-bound, can over-subscribe cores; image processing: CPU-bound, stay at or below `nproc`).

A sensible starting point: one worker per available CPU core for CPU-bound workloads, two to four times that for I/O-bound workloads. Watch the admin dashboard's "queue length over time" graph for the first week and adjust.

### Per-task concurrency limits

If a single task class can saturate workers (e.g. a heavy report generator), cap it with the task's `$rate` / `$timeout` properties or with a per-task `Configure::write('Queue.taskTimeout.MyTask', ...)` override. See [Configuration](/guide/configuration).

## Worker lifetime and restart cadence

Workers should be designed to exit and respawn periodically — this is the simplest defense against memory leaks, accumulated state, and any in-process cache that drifts from the database (e.g. a freshly migrated column that an older worker can't see yet — see the `captureOutput` regression that landed mid-2026).

- **`workerLifetime`** (`Configure::write('Queue.workerLifetime', 3600)`) — exit cleanly after N seconds. Process supervisor brings up a fresh worker. Default 0 means run forever; for production use a bounded value.
- **`workerRetry`** — number of retries on transient errors before a job is marked failed. Match this to your task idempotency story.
- **`exitwhennothingtodo`** — when set, the worker exits on its first empty poll. Suitable for cron-managed workers; leave off for long-running supervisor-managed processes.

### Recommended supervisor / systemd unit

A systemd unit shape that survives normal failures:

```ini
# /etc/systemd/system/cakephp-queue.service
[Unit]
Description=CakePHP Queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/app
ExecStart=/var/www/app/bin/cake queue worker --verbose
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Then `systemctl enable --now cakephp-queue.service`. The `Restart=always` plus a bounded `workerLifetime` means workers cycle on their own schedule and any crash recovers without manual intervention.

For multiple parallel workers, use a `cakephp-queue@.service` template and `systemctl enable --now cakephp-queue@1.service cakephp-queue@2.service ...`.

## Monitoring

The admin dashboard surfaces:

- `queue_processes` table — currently-running workers with last-seen timestamp.
- `queued_jobs` table — pending + recent jobs grouped by task, with progress, failure-message, and exit status.
- Per-task counters: queued / in-flight / failed / completed in the last 24h.

For external monitoring (Prometheus, Datadog, etc.), poll the same tables. The cheapest signals worth alerting on:

- **stale workers**: any row in `queue_processes` where `modified` is older than `defaultRequeueTimeout + 60s` is a worker that died without cleanup. The plugin auto-evicts these on next startup, but a persistent count > 0 means workers are crashing faster than they recover.
- **backlog growth**: `count(*)` of `queued_jobs WHERE completed IS NULL AND fetched IS NULL`. A monotonically rising number means you're under-provisioned.
- **per-task failure rate**: `count(*) WHERE failure_message IS NOT NULL AND created > now() - interval '1h'`, grouped by `job_task`. Alert when any task crosses your error budget.

The admin dashboard's "tips" sidebar contains the same SQL for quick eyeballing.

## Failure handling

The queue retries failed jobs up to `Queue.workerRetry` times before marking them dead. Dead jobs sit in `queued_jobs` with `failure_message` populated and `failed` set — they aren't auto-purged.

### Investigating a failure

1. **Admin dashboard → Failed jobs view** sorts by most recent. Each row links to its full payload + stack trace.
2. **`bin/cake queue clean`** moves completed and old failed jobs out; configure retention via `Queue.cleanuptimeout` (default 30 days).
3. **`bin/cake queue retry <job-id>`** requeues a dead job. Useful for transient failures (network blip, downstream service down) after you've fixed the cause.

### Dead-letter pattern

There's no built-in DLQ table yet — failed jobs stay in `queued_jobs`. The current workaround:

1. Set `Queue.cleanuptimeout` high enough (e.g. 90 days) that failed rows survive long enough to investigate.
2. Filter the dashboard by `failed IS NOT NULL` for the DLQ view.
3. Use `queue retry` to requeue, `queue remove` to drop.

Issue tracker has the formal DLQ feature on the roadmap; see `Per-task circuit breaker / dead-letter status` in the strategic items section of the [merged plugin review](https://github.com/dereuromark/cakephp-queue/issues).

## Schema migrations on live workers

Two cases worth knowing:

- **New column on `queued_jobs`** (e.g. when you migrate to a version that adds an `output` column): the `captureOutput()` auto-detect path re-runs the schema check on every job since the mid-2026 fix, so the new column takes effect on the next dispatched job. No worker restart required.
- **Anything more invasive** (dropped column, renamed table, FK changes): use a rolling restart. Bring workers down via `systemctl stop cakephp-queue@*`, run migrations, bring them back. Jobs in-flight at restart time fall under the standard `defaultRequeueTimeout` (180s default) — they get re-picked-up by a fresh worker if not finished.

## Multi-server deployments

If two app servers run cron-triggered workers against the same queue:

- Workers coordinate via the `queue_processes` table heartbeat + per-row `workerkey` claim, so there's no double-execution risk at the job level.
- BUT: if you also run `cakephp-queue-scheduler` on multiple hosts, the bundled `FileLock` is single-host by design. See the queue-scheduler operations guide for a multi-host advisory lock recipe.
- Run `bin/cake queue clean` from exactly **one** host (cron-driven, daily off-peak). Two simultaneous cleans don't corrupt anything but waste DB cycles.

## Common pitfalls

- **Tasks that swallow exceptions**: a task's `run()` method that catches `Throwable` and returns normally counts as success — even when the underlying work failed. If you must catch, re-throw `QueueException` to surface the failure to the worker.
- **Long sleeps inside `run()`**: anything that blocks longer than `defaultRequeueTimeout` will be considered abandoned and re-queued. Either chunk the work into smaller jobs or raise the per-task `$timeout`.
- **Worker started in CLI environment without DB access**: `bin/cake queue worker` reads the active CakePHP `Database.default` connection. If your CLI shell has a different DSN than your web shell, workers can't see jobs your web app created. Use the same `app_local.php` everywhere.
- **`captureOutput`-cached-stale**: fixed mid-2026 by re-checking schema per call. If you observe missing output on a long-running worker, restart it as a fallback. Migration ordering is also worth checking — add the column before deploying the code that writes to it.

## See also

- [Configuration](/guide/configuration) — every Configure key the worker reads at runtime.
- [Multi-Connection](/guide/multi-connection) — running queues against multiple databases.
- [Tips](/reference/tips) — debugging notes and one-off SQL queries for the queued_jobs table.
