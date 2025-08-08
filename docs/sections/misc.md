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

### Queue.Job.maxAttemptsExhausted
This event is triggered when a job has failed and exhausted all of its configured retry attempts.

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

The event data contains:
- `job`: The `QueuedJob` entity that failed
- `failureMessage`: The error message from the last failure

## Notes

`<TaskName>` is the complete class name without the Task suffix (e.g. Example or PluginName.Example).

Custom tasks should be placed in `src/Queue/Task/`.
Tasks should be named `SomethingTask` and implement the Queue "Task".

Plugin tasks go in `plugins/PluginName/src/Queue/Task/`.

A detailed Example task can be found in `src/Queue/Task/QueueExampleTask.php` inside this plugin.

Some more tips:
- If you copy an example, do not forget to adapt the namespace ("App")!
- For plugin tasks, make sure to load the plugin using the plugin prefix ("MyPlugin.MyName").
