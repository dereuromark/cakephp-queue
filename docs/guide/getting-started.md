# Getting started

After [installation](./installation), run these commands from your application directory. They create and process a harmless example job.

## Add a job

```sh
bin/cake queue add Queue.Example
```

The command stores a `Queue.Example` job in `queued_jobs`. It does not run the job itself.

## Start a worker

```sh
bin/cake queue run --max-runtime 20 --type Queue.Example -v
```

Look for the example task's success message and `Job Finished`. The worker continues polling until its lifetime is reached. The lifetime is checked between jobs; it does not interrupt a running task.

On the current branch, a worker without explicit lifetime configuration uses 120 seconds. Earlier releases may require `Queue.workerLifetime` or the `--max-runtime` option; the command above supplies that option.

Use `queue run` to process jobs. `queue worker` manages registered worker processes and does not start one.

## Inspect the queue

```sh
bin/cake queue info
bin/cake queue job
```

The second command lists recent jobs, then exits with an error code because no management action was supplied. To inspect one job, copy its ID and run `bin/cake queue job view 123`, replacing `123`.

## Queue from application code

From a controller or table using `fetchTable()`:

```php
$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
$job = $queuedJobsTable->createJob('Queue.Example');
```

For useful application work, [create your own task](./custom-tasks) and pass a small array of values or record IDs. Jobs store JSON data, so the worker receives arrays and scalars rather than the original entity objects.

When ready to process other task types, omit `--type Queue.Example`. Keep a worker running with [cron](./cron) or a [process supervisor](./operations), and make tasks safe to retry.
