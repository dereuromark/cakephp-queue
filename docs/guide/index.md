# Overview

Queue stores background jobs in your application's database. A separate CLI worker processes them, records completion, and retries failures according to each task's configuration.

Start with [Installation](./installation), then [Getting started](./getting-started). Use [Queueing jobs](./queueing-jobs) for payloads, priorities, references, and progress, or [Custom tasks](./custom-tasks) to implement your application's work.

## Operations

Configure worker lifetimes, timeouts, and retries in [Configuration](./configuration). Follow [Cron](./cron) or [Operations](./operations) to keep workers running. [Multi-connection](./multi-connection) covers separate queue databases.

Jobs can be executed more than once after crashes or timeouts. Design tasks to tolerate retries; the queue does not guarantee exactly-once execution. See [Limitations](../reference/limitations).

## Email and admin tools

[Mailing](./mailing) covers queue transports and custom email tasks. The [built-in tasks](../tasks/) include Email, Mailer, and Execute.

The optional [admin interface](../admin/) shows jobs and worker activity behind a required application access callback. [Real-time progress](./realtime-progress) uses an optional Mercure integration.
