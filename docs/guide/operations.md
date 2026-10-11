# Operations

Run workers with cron or a process supervisor. Keep the CLI database configuration aligned with the web application, and design tasks to tolerate retries.

The bare `queue worker` command lists recent workers and prints usage, but exits with an error status because no action was selected. Account for this when using it in monitoring scripts.

## Worker capacity

Start with a small `maxworkers` limit and measure queue latency, database load, and task duration. Each worker runs one task at a time. CPU-heavy tasks compete for cores; tasks waiting on remote services need timeouts on those calls.

A task's `unique` flag restricts its concurrent execution. `rate` delays repeats within a worker, and `costs` participates in the per-server budget. See [Configuration](./configuration) for their scope.

## Lifetimes and supervision

`workerLifetime` bounds how long a worker continues its loop. It is checked between jobs, so a task can finish after that lifetime has elapsed. `workerLifetimeJitter` spreads restarts across a fleet. `exitwhennothingtodo` is useful for cron workers that should exit after an empty poll.

A systemd unit can start and restart a worker:

```ini
# /etc/systemd/system/cakephp-queue.service
[Unit]
Description=CakePHP Queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/app
ExecStart=/var/www/app/bin/cake queue run --max-runtime 300 -v
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Replace the user and application path, then run `systemctl enable --now cakephp-queue.service`. Use separate units or a template unit for multiple workers, within your configured cap.

`queue run` processes jobs. `queue worker` lists and manages worker registrations.

## Monitoring

```sh
bin/cake queue info --format json
bin/cake queue worker
```

The JSON output includes unfinished, pending, scheduled, and aborted counts, worker information, and per-task statistics. Settings are omitted because they can contain callbacks or credentials.

Check backlog growth, task failures, and stale heartbeats. `staleHeartbeatThreshold` controls registration cleanup, with a runtime default of 90 seconds. `defaultRequeueTimeout` controls task claim timeouts and is a separate setting. A heartbeat can age while a long task is running; do not assume an old timestamp proves the process is dead.

The [admin interface](../admin/) shows jobs, failures, and registered workers. Limit its access with `Queue.adminAccess`.

## Failures and retries

A failed task is retried according to its `retries` setting. After exhausting attempts, its row is marked aborted and remains stored until reset, deletion, or configured cleanup. It is excluded from pending jobs.

```sh
bin/cake queue job view 123
bin/cake queue job reset 123
bin/cake queue job reset all
```

Replace `123` with the job ID. Inspect the payload and failure before resetting it. A task that catches an exception and returns normally counts as successful; throw a `QueueException` or another exception when work fails.

For deletion:

```sh
bin/cake queue job clean
bin/cake queue job flush --aborted
```

`clean` removes completed jobs older than `cleanuptimeout` and aborted jobs older than `cleanupAbortedTimeout`. The latter defaults to zero, retaining aborted jobs. `flush --aborted` deletes all aborted jobs; without that flag, `flush` deletes failed jobs.

There is no separate dead-letter table. Aborted rows in `queued_jobs` provide the retained failure list. Completed-job retention does not control aborted retention.

## Deployments and schema changes

Stop supervisor respawning while draining old workers. `bin/cake queue worker end all` requests that registered workers stop after their current job. Wait for those jobs to finish, migrate the relevant connection, deploy the code, and start fresh workers.

Restart workers after schema changes, including adding the `output` column. Output auto-detection reads the table's schema, which CakePHP may already have cached in that process. Rechecking the same table does not guarantee a fresh database description.

Interrupted jobs can be retried after their claim timeout. Increasing a timeout cannot make external effects atomic or guarantee exactly-once execution.

## Multiple servers

Enable `multiserver`, use distinct server identities, and point workers at the same intended queue database. Claims coordinate database work, but crashes and expired claims can still cause duplicate execution. SQLite does not provide the same row-locking path as MySQL or PostgreSQL; use a suitable database and test contention before running concurrent production workers.

See [Multi-connection](./multi-connection) for separate queues and [Limitations](../reference/limitations) for retry behavior.
