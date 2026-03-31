# Creating your own task

## Baking new Queue task and test
You can bake a new task and its test via
```
bin/cake bake queue_task MyTaskName [-p PluginName]
```

It will generate a `MyTaskNameTask` class in the right namespace.

It will not overwrite existing classes unless you explicitly force this (after prompting).

You can use `My/Sub/MyTaskNameTask` to create tasks in sub-namespaces.

## Detailed explanation

In most cases you wouldn't want to use the existing task, but just quickly build your own.
Put it into `src/Queue/Task/` as `{YourNameForIt}Task.php`.

You need to at least implement the `run()` method:
```php
namespace App\Queue\Task;

use Queue\Queue\Task;

class YourNameForItTask extends Task {

    /**
     * Timeout in seconds for this specific task.
     * Automatically capped to defaultRequeueTimeout if higher.
     *
     * @var ?int
     */
    public ?int $timeout = 20;

    /**
     * Number of retries for this task if it fails.
     *
     * @var ?int
     */
    public ?int $retries = 1;

    /**
     * @param array $data The array passed to QueuedJobsTable::createJob()
     * @param int $jobId The id of the QueuedJob entity
     * @return void
     */
    public function run(array $data, int $jobId): void {
        $fooBarsTable = $this->fetchTable('FooBars');
        if (!$fooBarsTable->doSth()) {
            throw new RuntimeException('Couldnt do sth.');
        }
    }

}
```
Make sure it throws an exception with a clear error message in case of failure.

Note: You can use the provided `Queue\Model\QueueException` if you do not need to include a stack trace.
This is usually the default inside custom tasks.

## DI Container

If you use the [Dependency Injection Container](https://book.cakephp.org/5/en/development/dependency-injection.html) provided by CakePHP you can also use
it inside your tasks.

### Constructor Injection

Tasks registered in the DI container can receive dependencies through their constructor, the same way CakePHP Components and Commands do.

First, register the task and its dependencies in your `Application::services()`:

```php
use App\Queue\Task\MyCustomTask;
use App\Service\MyService;

public function services(ContainerInterface $container): void {
    $container->add(MyService::class);
    $container->add(MyCustomTask::class)
        ->addArgument(MyService::class);
}
```

Then declare the dependency in your task's constructor:

```php
namespace App\Queue\Task;

use App\Service\MyService;
use Queue\Queue\Task;

class MyCustomTask extends Task {

    public function __construct(
        protected readonly MyService $myService,
    ) {
        parent::__construct();
    }

    public function run(array $data, int $jobId): void {
        $this->myService->doWork($data);
    }

}
```

The Processor injects its own runtime `Io` and `LoggerInterface` via `setIo()` / `setLogger()` after resolving the task, so your constructor only needs to declare your own dependencies. Call `parent::__construct()` with no arguments.

Tasks not registered in the container continue to work exactly as before.

### ServicesTrait

Alternatively, you can use the [ServicesTrait](https://github.com/dereuromark/cakephp-queue/blob/master/src/Queue/ServicesTrait.php) to pull services from the container at runtime inside `run()`:

```php
use Queue\Queue\ServicesTrait;

class MyCustomTask extends Task {

    use ServicesTrait;

    public function run(array $data, int $jobId): void {
        $myService = $this->getService(MyService::class);
    }
}
```

Note that `getService()` cannot be called in the constructor, only inside `run()` or other methods invoked after the container has been set.

## Organize tasks in sub folders

You can group tasks in sub namespaces.
E.g. `src/Queue/Task/My/Sub/{YourNameForIt}Task.php` would be found and used as `My/Sub/{YourNameForIt}`.
