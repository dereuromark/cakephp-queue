# Configuration

Set options under the `Queue` key in `config/app.php` or `app_local.php`. For a separate file, create `config/app_queue.php` returning an array, then load it in bootstrap with `Configure::load('app_queue')`.

```php
return [
    'Queue' => [
        'workerLifetime' => 60,
        'defaultRequeueTimeout' => 600,
        'defaultJobRetries' => 1,
        'sleeptime' => 10,
        'maxworkers' => 1,
    ],
];
```

The [example configuration](https://github.com/dereuromark/cakephp-queue/blob/cake6/config/app.example.php) contains suggested overrides. It is not loaded automatically, and some values differ from the runtime defaults below.

## Worker settings

| Key | Runtime default | Meaning |
| --- | --- | --- |
| `workerLifetime` | `120` on the current branch | Seconds before a worker exits between jobs |
| `workerLifetimeJitter` | `0` | Random seconds added to a bounded lifetime |
| `workerPhpTimeout` | Twice the worker lifetime | Value passed to PHP `set_time_limit()` |
| `defaultRequeueTimeout` | `600` | Default task claim timeout in seconds |
| `defaultJobRetries` | `1` | Retries after the first attempt |
| `sleeptime` | `10` | Seconds to sleep when no eligible job exists |
| `maxworkers` | `1` | Concurrent workers allowed per server; zero disables this cap |
| `exitwhennothingtodo` | `false` | Exit after the first empty poll |
| `gcprob` | `10` | Probability setting for cleanup between loop iterations |
| `staleHeartbeatThreshold` | `90` | Seconds before a worker heartbeat is treated as stale |
| `cleanuptimeout` | `2592000` | Retention for completed jobs; zero keeps them |
| `cleanupAbortedTimeout` | `0` | Retention after an aborted job's last fetch; zero keeps it |
| `multiserver` | `false` | Enable coordination across servers |
| `connection` | `default` | Queue datasource when no named connection is selected |
| `connections` | `[]` | Whitelist enabling multi-connection mode with two or more names |
| `log` | `false` when unset | Emit informational worker logs |
| `captureOutput` | Schema detection | Capture task output when the `output` column exists |
| `maxOutputSize` | `65536` | Stored output cap in bytes; zero uses the default |
| `canInterruptSleep` | `false` | Allow worker wake-up signals |

`--max-runtime` overrides the worker lifetime. A non-null CLI override also sets PHP's timeout to twice that value; zero removes both limits. Earlier releases may require explicit worker lifetime configuration. See [Upgrading](../reference/upgrading).

[PHP's timeout](https://www.php.net/manual/en/function.set-time-limit.php) is not a portable wall-clock limit: on Unix, time blocked in many I/O operations is excluded. Put timeouts on external calls and size task claim timeouts above the longest expected execution time.

## Task settings

Tasks expose `timeout`, `retries`, `rate`, `costs`, and `unique`. Override them on the class or by task name:

```php
return [
    'Queue' => [
        'defaultRequeueTimeout' => 600,
        'tasks' => [
            'Queue.ProgressExample' => [
                'timeout' => 300,
                'retries' => 2,
            ],
        ],
    ],
];
```

A task timeout is capped at `defaultRequeueTimeout`. Increase that global cap before increasing a task's timeout. A claim that expires while a worker is still running can be executed by another worker.

`rate` delays successive executions of a task within a worker; it is not a global concurrency cap. `unique` restricts concurrent execution of that task type. `costs` participates in the per-server worker cost budget. These controls do not replace application idempotency checks.

## Discovery and admin

`ignoredTasks` excludes task classes from discovery; `plugins` adds task sources. `skipExistenceCheck => true` bypasses task existence checks when creating jobs, so invalid task names can remain unprocessed.

`isSearchEnabled` controls optional Search integration. `isStatisticEnabled` enables the optional statistics view. It is not named `isStatsEnabled`. See [Admin](../admin/) for access, layout, and dashboard settings.

`executeAllowedCommands` is required for `Queue.Execute` when debug is disabled. See [Execute](../tasks/execute) before enabling command execution.
