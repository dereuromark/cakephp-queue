# Configuration

## Global configuration
The plugin allows some simple runtime configuration.
You may create a file called `app_queue.php` inside your `config` folder (NOT the plugins config folder) to set the following values:

- Seconds to sleep() when no executable job is found:

    ```php
    $config['Queue']['sleeptime'] = 10;
    ```

- Probability in percent of an old job cleanup happening:

    ```php
    $config['Queue']['gcprob'] = 10;
    ```

- Default timeout after which a job is requeued if the worker doesn't report back:

    ```php
    $config['Queue']['defaultRequeueTimeout'] = 180; // 3 minutes
    // Legacy: 'defaultworkertimeout' is deprecated but still supported
    ```

  **Important:** Individual task `timeout` property values should NOT exceed this `defaultRequeueTimeout` value. If a task has a timeout longer than the global requeue timeout, the job will be requeued before the task completes, causing duplicate execution. Always ensure `defaultRequeueTimeout` is greater than your longest task timeout (recommended: at least 2x the longest task timeout).

- Default number of retries if a job fails or times out:

    ```php
    $config['Queue']['defaultJobRetries'] = 3;
    // Legacy: 'defaultworkerretries' is deprecated but still supported
    ```

- Seconds of running time after which the worker process will terminate:

    ```php
    $config['Queue']['workerLifetime'] = 60; // 1 minute (same as respawn time)
    // Legacy: 'workermaxruntime' is deprecated but still supported
    ```

  **Important:** While 0 (unlimited) is technically allowed, it is **strongly discouraged**. Using 0 means workers will run indefinitely, which can lead to workers piling up if spawned faster than they can naturally terminate (e.g., when there are no jobs), potentially overloading your server.

  If you need workers to run for extended periods, use a very large value (e.g., 86400 for 24 hours). However, it's recommended to use shorter durations (e.g., 60-300 seconds) with cronjob respawning for better control and safety.

  **Note:** You can override this config value using the `--max-runtime` CLI option:
  ```bash
  bin/cake queue run --max-runtime 0     # Run indefinitely (only for local dev/debug)
  bin/cake queue run --max-runtime 300   # Run for 5 minutes
  ```

- Seconds of running time after which the PHP process of the worker will terminate:

    ```php
    $config['Queue']['workerPhpTimeout'] = 120; // 2 minutes
    // Legacy: 'workertimeout' is deprecated but still supported
    ```

  **Important:** While technically 0 (unlimited) is allowed for this setting, it is **strongly discouraged** if you are using a cronjob to permanently start new workers and if you do not exit on idle. This timeout serves as the last line of defense to prevent an excessive number of worker processes from accumulating, which could overload your server.

  Set this value high enough to never cut off running jobs, but low enough to keep the process count manageable. A good practice is to set it to at least 2x your `workerLifetime` value.

- Should a worker process quit when there are no more tasks for it to execute (true = exit, false = keep running):

    ```php
    $config['Queue']['exitwhennothingtodo'] = false;
    ```

- Minimum number of seconds before a cleanup run will remove a completed task (set to 0 to disable):

    ```php
    $config['Queue']['cleanuptimeout'] = 2592000; // 30 days
    ```

- Max workers (per server):

    ```php
    $config['Queue']['maxworkers'] = 3 // Defaults to 1 (single worker can be run per server)
    ```

- Multi-server setup:

    ```php
    $config['Queue']['multiserver'] = true // Defaults to false (single server)
    ```

  For multiple servers running either CLI/web separately, or even multiple CLI workers on top, make sure to enable this.

- Use a different connection:

    ```php
    $config['Queue']['connection'] = 'custom'; // Defaults to 'default'
    ```

- Ignore certain task classes to they don't end up in the generated SQL query. Can be used to filter out the example tasks classes shipped with the plugin, if you're not using them:

    ```php
    $config['Queue']['ignoredTasks'] = [
        'Queue\Queue\Task\CostsExampleTask',
        'Queue\Queue\Task\EmailTask',
        'Queue\Queue\Task\ExampleTask',
        'Queue\Queue\Task\ExceptionExampleTask',
        'Queue\Queue\Task\ExecuteTask',
        'Queue\Queue\Task\MonitorExampleTask',
        'Queue\Queue\Task\ProgressExampleTask',
        'Queue\Queue\Task\RetryExampleTask',
        'Queue\Queue\Task\SuperExampleTask',
        'Queue\Queue\Task\UniqueExampleTask',
    ]; // Defaults to []
    ```

Don't forget to load that config file with `Configure::load('app_queue');` in your bootstrap.

Example `app_queue.php`:

```php
return [
    'Queue' => [
        'workerLifetime' => 60,           // Worker process runs for 60 seconds
        'defaultRequeueTimeout' => 300,   // Jobs requeued after 5 minutes if not completed
        'defaultJobRetries' => 2,          // Retry failed jobs twice
        'sleeptime' => 15,                 // Sleep 15 seconds when no jobs
    ],
];
```

You can also drop the configuration into an existing config file (recommended) that is already been loaded.
The values above are the default settings which apply, when no configuration is found.

### Backend configuration

- isSearchEnabled: Set to false if you do not want search/filtering capability.
  This is auto-detected based on [Search](https://github.com/FriendsOfCake/search) plugin being available/loaded if not disabled.

- isStatsEnabled: Set to true to enable. This requires [chart.js](https://github.com/chartjs/Chart.js) asset to be available.
  You can also overwrite the template and as such change the asset library as well as the output/chart.

### Configuration tips

For the beginning maybe use not too many runners in parallel, and keep the runtimes rather short while starting new jobs every few minutes.
You can then always increase spawning of runners if there is a shortage.

## Task configuration

You can set two main things on each task as property: timeout and retries.
```php
    /**
     * Timeout for this task in seconds, after which the task is reassigned to a new worker.
     *
     * @var ?int
     */
    public ?int $timeout = 120;

    /**
     * Number of times a failed instance of this task should be restarted before giving up.
     *
     * @var ?int
     */
    public ?int $retries = 1;
```
Make sure you set the timeout high enough so that it could never run longer than this, otherwise you risk it being re-run while still being run.
It is recommended setting it to at least 2x the maximum possible execution length. See [Concurrent workers](limitations.md)

Set the retries to at least 1, otherwise it will never execute again after failure in the first run.

### Configure-based task overrides

You can also override task properties via Configure, which is useful for:
- Third-party tasks you cannot modify
- Environment-specific settings (dev vs production)
- Centralized configuration management

```php
$config['Queue']['tasks'] = [
    'Queue.ProgressExample' => [
        'timeout' => 300,   // Override the task's timeout
        'retries' => 5,     // Override the task's retries
    ],
    'MyPlugin.HeavyTask' => [
        'timeout' => 600,
        'rate' => 10,
        'costs' => 50,
        'unique' => true,
    ],
];
```

The priority order is: Configure override > Task class property > Global default.
