# Limitations

## Jobs can execute more than once

A crashed or timed-out worker can leave a claim that another worker later retries. If the original worker is still executing, both can perform the same operation. A claim also cannot make database updates and an external API call one atomic transaction.

Choose task timeouts above the maximum expected duration, within `defaultRequeueTimeout`, and make tasks safe to retry. Use stable operation IDs, database constraints, or the target service's idempotency support where appropriate.

## Task concurrency

A task's `unique` flag restricts concurrent execution of that task type. `rate` applies within each worker, and `costs` limits the per-server cost budget. These controls do not guarantee exactly-once effects and do not provide arbitrary per-tenant concurrency limits.

The `unique` option passed to `createJob()` is separate: it deduplicates pending jobs with the same task and reference. It does not prevent a completed operation from being queued again.
