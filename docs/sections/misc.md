# Misc

## Logging

By default, errors are always logged, and with log enabled also the execution of a job.
Make sure you add this to your config:
```php
'Log' => [
    ...
    'queue' => [
        'className' => ...,
        'type' => 'queue',
        'levels' => ['info'],
        'scopes' => ['queue'],
    ],
],
```

When debugging (not using `--quiet`/`-q`) on "run", it will also log the worker run and end.

You can disable info logging by setting `Queue.log` to `false` in your config.

## Resetting
You can reset all failed jobs from CLI and web backend.
With web backend you can reset specific ones, as well.

From CLI you run this to reset all at once:
```
bin/cake queue reset
```

## Rerunning
You can rerun successfully run jobs if they are not yet cleaned out. Make sure your cleanup timeout is high enough here.
Usually weeks or months is a good balance to have those still stored for this case.

This is especially useful for local development or debugging, though. As you would otherwise have to manually trigger or import the job all the time.

From CLI you run this to rerun all of a specific job type at once:
```
bin/cake queue rerun FooBar
```
You can add a reference to rerun a specific job.

## Using custom finder
You can use a convenience finder for tasks that are still queued, that means not yet finished.
```php
$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
$query = $queuedJobsTable->find('queued')->...;
```
This includes also failed ones if not filtered further using `where()` conditions.

## Events
The Queue plugin dispatches events to allow you to hook into the queue processing lifecycle.
These events are useful for monitoring, logging, and integrating with external services.

### Queue.Job.created
Fired when a new job is added to the queue (producer side).

```php
use Cake\Event\EventInterface;
use Cake\Event\EventManager;

EventManager::instance()->on('Queue.Job.created', function (EventInterface $event) {
    $job = $event->getData('job');
    // Track job creation for monitoring
});
```

Event data:
- `job`: The `QueuedJob` entity that was created

### Queue.Job.started
Fired when a worker begins processing a job (consumer side).

```php
EventManager::instance()->on('Queue.Job.started', function (EventInterface $event) {
    $job = $event->getData('job');
    // Start tracing/monitoring span
});
```

Event data:
- `job`: The `QueuedJob` entity being processed

### Queue.Job.completed
Fired when a job finishes successfully.

```php
EventManager::instance()->on('Queue.Job.completed', function (EventInterface $event) {
    $job = $event->getData('job');
    // Mark trace as successful
});
```

Event data:
- `job`: The `QueuedJob` entity that completed

### Queue.Job.failed
Fired when a job fails (on every failure attempt).

```php
EventManager::instance()->on('Queue.Job.failed', function (EventInterface $event) {
    $job = $event->getData('job');
    $failureMessage = $event->getData('failureMessage');
    $exception = $event->getData('exception');
    // Mark trace as failed, log error
});
```

Event data:
- `job`: The `QueuedJob` entity that failed
- `failureMessage`: The error message from the failure
- `exception`: The exception object (if available)

### Queue.Job.maxAttemptsExhausted
Fired when a job has failed and exhausted all of its configured retry attempts.

```php
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Log\Log;

EventManager::instance()->on('Queue.Job.maxAttemptsExhausted', function (EventInterface $event) {
    $job = $event->getData('job');
    $failureMessage = $event->getData('failureMessage');

    // Log the permanent failure
    Log::error(sprintf(
        'Job %d (%s) permanently failed after %d attempts: %s',
        $job->id,
        $job->job_task,
        $job->attempts,
        $failureMessage
    ));

    // Send notification email
    //$mailer->send('jobFailed', [$job, $failureMessage]);

    // Or push to external monitoring service
    //$monitoring->notifyJobFailure($job);
});
```

Event data:
- `job`: The `QueuedJob` entity that failed
- `failureMessage`: The error message from the last failure

### Sentry Integration

The plugin includes a `CakeSentryEventBridge` listener that bridges Queue events to
[lordsimal/cakephp-sentry](https://github.com/LordSimal/cakephp-sentry) for queue monitoring.

To enable Sentry integration, register the bridge in your `Application::bootstrap()`:

```php
use Cake\Event\EventManager;
use Queue\Event\CakeSentryEventBridge;

// Enable Sentry queue monitoring
EventManager::instance()->on(new CakeSentryEventBridge());
```

This automatically dispatches the `CakeSentry.Queue.*` events that the Sentry plugin listens to:
- `CakeSentry.Queue.enqueue` - when a job is created
- `CakeSentry.Queue.beforeExecute` - when a job starts processing
- `CakeSentry.Queue.afterExecute` - when a job completes or fails

#### Trace Propagation

For distributed tracing to work across producer and consumer, add Sentry trace headers
to job data when creating jobs:

```php
$data = [
    'your_data' => 'here',
];

// Add Sentry trace headers if Sentry SDK is available
if (class_exists(\Sentry\SentrySdk::class)) {
    $data['_sentry_trace'] = \Sentry\getTraceparent();
    $data['_sentry_baggage'] = \Sentry\getBaggage();
}

$queuedJobsTable->createJob('MyTask', $data);
```

The bridge will automatically extract these headers and pass them to the Sentry plugin.

## Notes

`<TaskName>` is the complete class name without the Task suffix (e.g. Example or PluginName.Example).

Custom tasks should be placed in `src/Queue/Task/`.
Tasks should be named `SomethingTask` and implement the Queue "Task".

Plugin tasks go in `plugins/PluginName/src/Queue/Task/`.

A detailed Example task can be found in `src/Queue/Task/QueueExampleTask.php` inside this plugin.

Some more tips:
- If you copy an example, do not forget to adapt the namespace ("App")!
- For plugin tasks, make sure to load the plugin using the plugin prefix ("MyPlugin.MyName").
