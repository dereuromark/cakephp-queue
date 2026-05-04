# Guide

The Queue plugin runs background jobs out of your CakePHP database — no Redis, no RabbitMQ, no extra infrastructure. Jobs are persisted, retried on failure, and processed by one or more worker processes you trigger via cron, supervisor, or systemd.

## Where to start

- [Basic Setup](/guide/basic-setup) — install, load the plugin, run migrations.
- [Queueing Jobs](/guide/queueing-jobs) — create jobs from your app code, with priorities, references, and progress tracking.
- [Custom Tasks](/guide/custom-tasks) — write your own task classes.

## Operating the queue

- [Configuration](/guide/configuration) — runtime tuning (worker lifetime, timeouts, retries, multi-server).
- [Cron Setup](/guide/cron) — start workers on a schedule.
- [Multi-Connection](/guide/multi-connection) — run queues against multiple databases.
- [Real-Time Progress](/guide/realtime-progress) — Mercure / SSE for live progress UIs.

## Email handling

- [Mailing](/guide/mailing) — queueing emails via QueueTransport, custom email tasks, and the built-in tasks reference.

For built-in tasks (Execute, Email, Mailer), see [Tasks](/tasks/).
For the admin UI, see [Admin Dashboard](/admin/).
