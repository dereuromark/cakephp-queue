# Tips for Development

## IDE support

With [IdeHelper](https://github.com/dereuromark/cakephp-ide-helper/) plugin you can get typehinting and autocomplete for your createJob() calls.
Especially if you use PHPStorm, this will make it possible to get support here.

Include that plugin, set up your generator config and run e.g. `bin/cake phpstorm generate`.

If you use `$this->addPlugin('Queue', ['bootstrap' => true, ...])`, the necessary config is already auto-included (recommended).
Otherwise you can manually include the Queue plugin generator tasks in your `config/app.php` on project level:

```php
use Queue\Generator\Task\QueuedJobTask;

return [
    ...
    'IdeHelper' => [
        'generatorTasks' => [
            QueuedJobTask::class,
        ],
    ],
];
```

If you now click into the first argument of `createJob()`, it should show you the available ones to quickly select:

![](resources/autocomplete.png)

## Only pass identification data if possible

If you have larger data sets, or maybe even objects/entities, do not pass those.
They would not survive the json_encode/decode part and will maybe even exceed the text field in the database.

Instead, pass only the ID of the entity, and get your data in the Task itself.
If you have other larger chunks of data, store them somewhere and pass the path to this file.

## Generating links/URLS in CLI
When you have Queue tasks and templates that need to create URLs, make sure you followed the core documentation
on setting the `App.fullBaseUrl` config on your server.
CLI itself does not know the URL your website is running on, so this value must be configured here for the generation to work.

## Multi Server Setup
When working with multiple CLI servers there are several requirements for it to work smoothly:

Make sure `env('SERVER_NAME')` or `gethostname()` return a unique name per server instance.
This is required as PID alone is now not unique anymore.
Each worker then registers itself as combination of `PID + server name`.

You can not kill workers on a different server, you can only mark them as "to be terminated".
The next run the worker registers this and auto-terminates early.

Removing PIDs from DB will yield the same result then, a "soft-killing".

## Ending workers
The "soft-killing" should be preferred over "hard-killing".
It will make sure the worker process will finish the current job and then abort right afterwards.

This is useful when deploying a code or DB migration change and you want the "old workers" based on the old code
to not process any new incoming jobs after deployment.

In this case, make sure one of your first calls of the deployment script is
```
bin/cake queue worker end all -q
```

To avoid further deployment issues, also try to keep the runtime per worker to only a few minutes.
You can additionally do this call at the end of the deployment script to make sure any workers started in the meantime
will also be aborting early.

### Ending workers per server
A useful feature when having multiple servers and workers, and deploying separately, is to only end the workers on the server you are deploying to.

For this make sure you have either `env('SERVER_NAME')` or `gethostname()` return a unique name per server instance.
These are stored in the processes and as such you can then end them per instance that deploys.

This snippet should be in the deploy script then instead.
```
bin/cake queue worker end server -q
```

You can check/verify the current server name using `bin/cake queue stats`.

If you want to test locally, type `export SERVER_NAME=myserver1` and then run the above.

### Rate limiting and throttling

The following configs can be made specific per Task, hardcoded on the class itself:
- rate (defaults to `0` = disabled)
- unique (defaults to `false` = disabled)
- costs (defaults to `0` = disabled)

Check if you need to use "rate" config (> 0) to avoid tasks being run too often/fast per worker per timeframe.
Currently you cannot rate limit it more globally however. You can use `unique` and `costs` config, however, to more globally restrict parallel runs for job types.


Note: Once any task has either "unique" or "costs" enabled, the worker has to do a pre-query to fetch the data for this.
Thus it is disabled by default for trivial use cases.

## Killing workers

First of all: Make sure you don't run workers with `workermaxruntime` and `workertimeout` of `0`.
Then they would at least not run forever, and might pile up only if you start them faster then they terminate.
That can overload the server.

### Via tool

You can kill workers from the backend or the command line.
Make sure you have set up the workers with the same user (www-data usually) as the user that tries to kill them, or it will not work.

### Manually

Manually killing workers can be done using `kill -15 PID`. Replace PID with the PID number (e.g. `kill -15 21212`).

To find out what queue processes are currently running, use

    ps aux | grep php

Then you can kill them gracefully with `-15` (or forcefully with `-9`, not recommended).

Locally, if you want to kill them all, usually `killapp -15 php` does the trick.
Do not run this with production ones, though.

The console kill commands are also registered here. So if you run a worker locally,
and you enter `Ctrl+C` or alike, it will also hard-kill this worker process.

## Use DTOs
Using [CakeDto](https://github.com/dereuromark/cakephp-dto) plugin you can make your code much more reliable, testable
and developer-friendly.

Set up a DTO per task in your `dto.xml`, e.g.
```xml
<dto name="OrderUpdateNotificationQueueData" immutable="true">
    <field name="orderId" type="int" required="true"/>
    <field name="type" type="string" required="true"/>
    ...
</dto>
```
Instead of a plain array you can now rely on a clean API for input:
```php
$dataDto = OrderUpdateNotificationQueueDataDto::createFromArray([
    'orderId' => $order->id,
    'type' => 'orderConfirmationToCustomer',
]);
$this->getTableLocator()->get('Queue.QueuedJobs')->createJob('OrderUpdateNotification', $dataDto);
```
Any of the fields not provided or defined will throw a clear exception.

Same then for the counterpart within the task:
```php
public function run(array $data, int $jobId): void {
    $queueData = OrderUpdateNotificationQueueDataDto::createFromArray($data);

    $order = $this->fetchTable('Orders')->get($queueData->getOrderId(), contain: ['OrderItems']);
    $this->getMailer('OrderConfirmation')->send($queueData->getType(), [$order]);
}
```

PHPStan together with tests can now fully monitor and assert necessary data.
