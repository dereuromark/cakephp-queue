# Using built-in Execute task

The built-in task directly runs on the same path as your app, so you can use relative paths or absolute ones:
```php
$data = [
    'command' => 'bin/cake importer run',
    'content' => $content,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Execute', $data);
```

The task automatically captures stderr output into stdout. If you don't want this, set "redirect" to false.
It also escapes by default using "escape" true. Only disable this if you trust the source.

By default, it only allows return code `0` (success) to pass. If you need different accepted return codes, pass them as "accepted" array.
If you want to disable this check and allow any return code to be successful, pass `[]` (empty array).

*Warning*: This can essentially execute anything on CLI. Make sure you never expose this directly as free-text input to anyone.
Use only predefined and safe code-snippets here!
