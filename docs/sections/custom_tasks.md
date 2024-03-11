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
     * @var int
     */
    public $timeout = 20;

    /**
     * @var int
     */
    public $retries = 1;

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

## DI Container Example

If you use the [Dependency Injection Container](https://book.cakephp.org/5/en/development/dependency-injection.html) provided by CakePHP you can also use
it inside your tasks.

```php
use Queue\Queue\ServicesTrait;

class MyCustomTask extends Task {

    use ServicesTrait;

    public function run(array $data, int $jobId): void {
        $myService = $this->getService(MyService::class);
    }
}
```

As you see here you have to add the [ServicesTrait](https://github.com/dereuromark/cakephp-queue/blob/master/src/Queue/ServicesTrait.php) to your task which then allows you to use the `$this->getService()` method.

## Organize tasks in sub folders

You can group tasks in sub namespaces.
E.g. `src/Queue/Task/My/Sub/{YourNameForIt}Task.php` would be found and used as `My/Sub/{YourNameForIt}`.
