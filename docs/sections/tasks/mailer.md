# Using built-in Mailer task

Sending reusable templated emails the easy way:
```php
$data = [
    'class' => TestMailer::class,
    'action' => 'testAction',
    'vars' => [...],
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Mailer', $data);
```
Since we are not passing in an object, but a class string and settings, this is also JsonSerializer safe.
