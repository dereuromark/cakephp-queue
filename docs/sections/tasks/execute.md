# Using built-in Execute task

The Execute task allows you to run shell commands asynchronously through the queue system.

## Basic usage

The task runs commands in the same working directory as your application:

```php
use Cake\ORM\Locator\LocatorAwareTrait;

// Simple command
$data = [
    'command' => 'bin/cake importer run',
];
$queuedJobsTable = $this->fetchTable('Queue.QueuedJobs');
$queuedJobsTable->createJob('Queue.Execute', $data);
```

## Command with parameters

You can pass parameters separately for better control:

```php
$data = [
    'command' => 'bin/cake',
    'params' => ['importer', 'run', '--verbose'],
];
$queuedJobsTable->createJob('Queue.Execute', $data);
```

The `params` array will be joined with spaces and appended to the command.

## Configuration options

### escape (default: true)

By default, the command and each entry in `params` are escaped per token using `escapeshellarg()` so that arguments cannot break out into additional shell tokens (argument injection):

```php
$data = [
    'command' => 'bin/cake',
    'params' => ['importer', 'run'],
    'escape' => true, // Default - command and params escaped per token
];
```

**Only disable this if you completely trust the source!**

```php
$data = [
    'command' => 'some-trusted-command',
    'escape' => false, // Use with extreme caution
];
```

`escape => false` is hard-rejected unless `debug` is `true`.

### Queue.executeAllowedCommands (production allow-list)

When `debug` is disabled, the `command` value MUST appear verbatim in the `Queue.executeAllowedCommands` allow-list, otherwise the task throws before invoking `exec()`:

```php
// config/app.php (or app_local.php)
'Queue' => [
    'executeAllowedCommands' => [
        'bin/cake',
        '/usr/bin/php',
    ],
],
```

If the allow-list is unset or empty in production, every Execute job is rejected. This protects against an attacker who can write to `queued_jobs` (DB-level compromise, or upstream code that pipes user input into `createJob('Queue.Execute', ...)`) from steering the exec call.

### redirect (default: true)

By default, stderr output is redirected to stdout (using `2>&1`). Disable if you need separate error handling:

```php
$data = [
    'command' => 'bin/cake importer run',
    'redirect' => false, // Keep stderr separate
];
```

### accepted (default: [0])

By default, only exit code `0` (success) is accepted. You can configure different accepted return codes:

```php
$data = [
    'command' => 'some-command',
    'accepted' => [0, 1, 2], // Accept these exit codes as success
];
```

To accept any return code (disable checking):

```php
$data = [
    'command' => 'some-command',
    'accepted' => [], // Empty array = accept any exit code
];
```

### log (default: false)

Enable logging of command execution details:

```php
$data = [
    'command' => 'bin/cake importer run',
    'log' => true, // Log command, exit code, and output
];
```

This logs the server, command, exit code, and output to the configured logger.

## Complete example

```php
$data = [
    'command' => 'bin/cake',
    'params' => ['cleanup', 'cache', '--force'],
    'escape' => true,
    'redirect' => true,
    'accepted' => [0],
    'log' => true,
];
$queuedJobsTable->createJob('Queue.Execute', $data);
```

## Adding from CLI

You can also add Execute jobs from the command line:

```bash
bin/cake queue add Execute "sleep 10s"
```

For commands with parameters, use quotes:

```bash
bin/cake queue add Execute "bin/cake importer run --verbose"
```

## Security warning

**Important:** The Execute task can run any shell command. Never expose this to user input directly. Only use predefined, safe command snippets in your application code.
