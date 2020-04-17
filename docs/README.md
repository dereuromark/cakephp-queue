# CakePHP Queue Plugin Documentation


## Installation
```
composer require dereuromark/cakephp-queue
```
Load the plugin in your `src/Application.php`'s bootstrap() using:
```php
$this->addPlugin('Queue');
```
If you want to also access the backend controller (not just using CLI), you need to use
```php
$this->addPlugin('Queue', ['routes' => true]);
```

Run the following command in the CakePHP console to create the tables using the Migrations plugin:
```sh
bin/cake migrations migrate -p Queue
```

It is also advised to have the `posix` PHP extension enabled.


## Configuration

### Global configuration
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
    $config['Queue']['defaultworkertimeout'] = 1800;
    ```

- Default number of retries if a job fails or times out:

    ```php
    $config['Queue']['defaultworkerretries'] = 3;
    ```

- Seconds of running time after which the worker will terminate (0 = unlimited):

    ```php
    $config['Queue']['workermaxruntime'] = 120;
    ```

    *Warning:* Do not use 0 if you are using a cronjob to permanantly start a new worker once in a while and if you do not exit on idle.

- Seconds of running time after which the PHP process of the worker will terminate (0 = unlimited):

    ```php
    $config['Queue']['workertimeout'] = 120 * 100;
    ```

    *Warning:* Do not use 0 if you are using a cronjob to permanently start a new worker once in a while and if you do not exit on idle. This is the last defense of the tool to prevent flooding too many processes. So make sure this is long enough to never cut off jobs, but also not too long, so the process count stays in manageable range.

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

Don't forget to load that config file with `Configure::load('app_queue');` in your bootstrap.
You can also use `$this->addPlugin('Queue', ['bootstrap' => true]);` which will load your `app_queue.php` config file automatically.

Example `app_queue.php`:

```php
return [
    'Queue' => [
        'workermaxruntime' => 60,
        'sleeptime' => 15,
    ],
];
```

You can also drop the configuration into an existing config file (recommended) that is already been loaded.
The values above are the default settings which apply, when no configuration is found.

#### Backend configuration

- isSearchEnabled: Set to false if you do not want search/filtering capability.
This is auto-detected based on [Search](https://github.com/FriendsOfCake/search) plugin being available/loaded if not disabled.

- isStatsEnabled: Set to true to enable. This requires [chart.js](https://github.com/chartjs/Chart.js) asset to be available.
You can also overwrite the template and as such change the asset library as well as the output/chart.


#### Configuration tips

For the beginning maybe use not too many runners in parallel, and keep the runtimes rather short while starting new jobs every few minutes.
You can then always increase spawning of runners if there is a shortage.

### Task configuration

You can set two main things on each task as property: timeout and retries.
```php
    /**
     * Timeout for this task in seconds, after which the task is reassigned to a new worker.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Number of times a failed instance of this task should be restarted before giving up.
     *
     * @var int
     */
    public $retries = 1;
```
Make sure you set the timeout high enough so that it could never run longer than this, otherwise you risk it being re-run while still being run.
It is recommended setting it to at least 2x the maximum possible execution length. See "Concurrent workers" below.

Set the retries to at least 1, otherwise it will never execute again after failure in the first run.

## Writing your own task

In most cases you wouldn't want to use the existing task, but just quickly build your own.
Put it into `/src/Shell/Task/` as `Queue{YourNameForIt}Task.php`.

You need to at least implement the run method:
```php
namespace App\Shell\Task;

...

class QueueYourNameForItTask extends QueueTask implements QueueTaskInterface {

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
    public function run(array $data, $jobId) {
        $this->loadModel('FooBars');
        if (!$this->FooBars->doSth()) {
            throw new RuntimeException('Couldnt do sth.');
        }
    }

}
```
Make sure it throws an exception with a clear error message in case of failure.

Note: You can use the provided `Queue\Model\QueueException` if you do not need to include a strack trace.
This is usually the default inside custom tasks.

## Usage

Run the following using the CakePHP shell:

* Display Help message:

        bin/cake queue

* Try to call the cli add() function on a task:

        bin/cake queue add <TaskName>

    Tasks may or may not provide this functionality.

* Run a queue worker, which will look for a pending task it can execute:

        bin/cake queue runworker

    The worker will always try to find jobs matching its installed Tasks.


Most tasks will not be triggered from the console, but from the APP code.
You will need to use the model access for QueuedJobs and the createJob() function to do this.

The `createJob()` function takes three arguments.
- The first argument is the name of the type of job that you are creating.
- The second argument is optional, but if set must be an array of data and will be passed as a parameter to the `run()` function of the worker.
- The third argument is options (`'notBefore'`, `'priority'`, `'group'`).

For sending emails, for example:

```php
// In your controller
$this->loadModel('Queue.QueuedJobs');
$this->QueuedJobs->createJob('Email', ['to' => 'user@example.org', ...]);

// Somewhere in the model or lib
TableRegistry::getTableLocator()->get('Queue.QueuedJobs')->createJob('Email',
    ['to' => 'user@example.org', ...]);
```

It will use your custom APP `QueueEmailTask` to send out emails via CLI.

Important: Do not forget to set your [domain](https://book.cakephp.org/3.0/en/core-libraries/email.html#sending-emails-from-cli) when sending from CLI.


### Running only specific tasks per worker
You can filter "running" by group or even type:
```
bin/cake queue runworker -g MyGroup
bin/cake queue runworker -t MyType,AnotherType,-ThisOneToo
bin/cake queue runworker -t "-ThisOneNot"
```
Use `-` prefix to exclude. Note that you might need to use `""` around the value then to avoid it being seen as option key.

That can be helpful when migrating servers and you only want to execute certain ones on the new system or want to test specific servers.

### Avoiding parallel (re)queueing

For some background-tasks you will want to make sure only a single instance of this type is currently run.
In your logic you can check on this using `isQueued()` and a unique reference:
```php
    /**
     * @return \Cake\Http\Response|null
     */
    public function triggerImport() {
        $this->request->allowMethod('post');

        $this->loadModel('Queue.QueuedJobs');
        if ($this->QueuedJobs->isQueued('my-import', 'Execute')) {
            $this->Flash->error('Job already running');

            return $this->redirect($this->referer(['action' => 'index']));
        }

        $this->QueuedJobs->createJob(
           'Execute',
            ['command' => 'bin/cake importer run'],
            ['reference' => 'my-import', 'priority' => 2]
        );

        $this->Flash->success('Job triggered, will only take few seconds :)');

        return $this->redirect($this->referer(['action' => 'index']));
    }
```
So if someone clicks on the button again before the job is finished, he will not be able to trigger a new run:
```php
<?= $this->Form->postLink(__('Trigger Import'), ['action' => 'triggerImport'], ['confirm' => 'Sure?']) ?>
```

For more complex use cases, you can manually use `->find()->where()`, of course.

Note that the 2nd argument (job type) is optional, but recommended. If you do not use it, make sure your reference is globally unique.

### Updating progress/status

The `createJob()` method returns the entity. So you can store the ID and at any time ask the queue about the status of this job.

```php
// Within your regular web application
$job = $this->QueuedJobs->createJob(...);
$id = $job->id;
// Store

// Inside your Queue task, if you know the total records:
$totalRecords = count($records);
foreach ($records as $i => $record) {
    $this->processImageRendering($record);
    $this->QueuedJobs->updateProgress($id, ($i + 1) / $totalRecords);
}
```

You can, independently from the progress field, also use a status (string) field to give feedback.
See this example implementation:

```php
class FooTask extends QueueTask {

    public function run(array $data, $jobId) {
        // Initializing
        $jobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
        $foo = new Foo();

        // Part one
        $jobsTable->updateAll(
            ['status' => 'Doing the first thing'],
            ['id' => $jobId]
        );
        $foo->doFirstPartOfTask();
        $jobsTable->updateProgress($jobId, 33);

        // Part two
        $jobsTable->updateAll(
            ['status' => 'Doing the next thing'],
            ['id' => $jobId]
        );
        $foo->doNextPartOfTask();
        $jobsTable->updateProgress($jobId, 66);

        // Part three
        $jobsTable->updateAll(
            ['status' => 'Doing the last thing'],
            ['id' => $jobId]
        );
        $foo->doLastPartOfTask();
        $jobsTable->updateProgress($jobId, 100);

        // Done
        $jobsTable->updateAll(
            ['status' => 'Done doing things'],
            ['id' => $jobId]
        );

        return true;
    }
}
```

Get progress status in web site and display:
```php
$job = $this->QueuedJobs->get($id);

$progress = $job->progress; // A float from 0 to 1
echo $this->Number->toPercentage($progress, 0, ['multiply' => true]) . '%'; // Outputs 87% for example

$status = $job->status; // A string, make sure to escape
echo h($status); // Outputs "Doing the last thing" for example
```

#### Progress Bar
Using Tools plugin 1.9.6+ you can also use the more visual progress bar (or any custom one of yours):

```php
echo $this->QueueProgress->progressBar($queuedJob, 18);
```
![HTML5](bar_text.png)

The length refers to the amount of chars to display.

Using Tools plugin 1.9.7+ you can even use HTML5 progress bar (easier to style using CSS).
For this it is recommended to add the textual one from above as fallback, though:
```php
$textProgressBar = $this->QueueProgress->progressBar($queuedJob, 18);
echo $this->QueueProgress->htmlProgressBar($queuedJob, $textProgressBar);
```

![HTML5](bar_html.png)

The text one will only be visible for older browsers that do not support the HTML5 tag.

Make sure you loaded the helper in your AppView class.

By default it first tries to use the actual `progress` stored as value 0...1.
If that field is `null`, it tries to use the statistics of previously finished jobs of the same task
to determine average length and displays the progress based on this.

That also means you should set a high value for `cleanuptimeout` config (weeks/months) to make sure the average
runtime data is available and meaningful.

#### Timeout Progress Bar
For those jobs that are created with a run time in the future (`notbefore`), you can also display progress
until they are supposed to be run:

```php
echo $this->QueueProgress->timeoutProgressBar($queuedJob, 18);
```
It shows the progress as current time between `created` and `notbefore` boundaries more visually.

Using Tools plugin 1.9.7+ you can even use HTML5 progress bar (easier to style using CSS).
For this it is recommended to add the textual one from above as fallback, though:
```php
$textTimeoutProgressBar = $this->QueueProgress->timeoutProgressBar($queuedJob, 18);
echo $this->QueueProgress->htmlTimeoutProgressBar($queuedJob, $textTimeoutProgressBar);
```

### Logging

By default errors are always logged, and with log enabled also the execution of a job.
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

When debugging (using -v) on the runworker, it will also log the worker run and end.

You can disable info logging by setting `Queue.log` to `false` in your config.

### Resetting
You can reset all failed jobs from CLI and web backend.
With web backend you can reset specific ones, as well.

From CLI you run this to reset all at once:
```
bin/cake queue reset
```

### Rerunning
You can rerun successfully run jobs if they are not yet cleaned out. Make sure your cleanup timeout is high enough here.
Usually weeks or months is a good balance to have those still stored for this case.

This is especially useful for local development or debugging, though. As you would otherwise have to manually trigger or import the job all the time.

From CLI you run this to rerun all of a specific job type at once:
```
bin/cake queue rerun FooBar
```
You can add an additional reference to rerun a specific job.

### Using custom finder
You can use a convenience finder for tasks that are still queued, that means not yet finished.
```php
$query = $this->QueuedJobs->find('queued')->...;
```
This includes also failed ones if not filtered further using `where()` conditions.

### Notes

`<TaskName>` may either be the complete classname (eg. QueueExample) or the shorthand without the leading "Queue" (e.g. Example).

Also note that you dont need to add the type ("Task"): `bin/cake queue add SpecialExample` for QueueSpecialExampleTask.

Custom tasks should be placed in src/Shell/Task.
Tasks should be named `QueueSomethingTask.php` and implement a "QueueSomethingTask", keeping CakePHP naming conventions intact. Custom tasks should extend the `QueueTask` class (you will need to include this at the top of your custom task file: `use Queue\Shell\Task\QueueTask;`).

Plugin tasks go in plugins/PluginName/src/Shell/Task.

A detailed Example task can be found in src/Shell/Task/QueueExampleTask.php inside this folder.

If you copy an example, do not forget to adapt the namespace!


## Setting up the trigger cronjob

As outlined in the [book](http://book.cakephp.org/3.0/en/console-and-shells/cron-jobs.html) you can easily set up a cronjob
to start a new worker.

The following example uses "crontab":

    */10  *  *  *  *  cd /full/path/to/app && bin/cake queue runworker -q

Make sure you use `crontab -e -u www-data` to set it up as `www-data` user, and not as root etc.

This would start a new worker every 10 minutes. If you configure your max life time of a worker to 15 minutes, you
got a small overlap where two workers would run simultaneously. If you lower the 10 minutes and raise the lifetime, you
get quite a few overlapping workers and thus more "parallel" processing power.
Play around with it, but just don't shoot over the top.

Also don't forget to set Configure key `'Queue.maxworkers'` to a reasonable value per server.
If, for any reason, some of the jobs should take way longer, you want to avoid additional x workers to be started.
It will then just not start now ones beyond this count until the already running ones are finished.
This is an important server protection to avoid overloading.


## Admin backend

The plugin works completely without it, by just using the CLI shell commands.
But if you want to browse the statistics via URL, you can enable the routing for it (see above) and then access `/admin/queue`
to see how status of your queue, statistics and settings.
Please note that this requires the [Tools plugin](https://github.com/dereuromark/cakephp-tools) to be loaded if you do not customize the view templates on project level.
Also make sure you loaded the helpers needed (Tools.Format, Tools.Time as Time, etc).

By default the templates should work fine in both Foundation (v5+) and Bootstrap (v3+).
Copy-and-paste to project level for any customization here.

### Using backend actions
You can add buttons to your specific app views to re-run a failed job, or to remove it.
```php
$this->loadHelper('Queue.Queue');
if ($this->Queue->failed($queuedJob)) {
    $query = ['redirect' => $this->request->getAttribute('here')];
    echo $this->Form->postLink(
        'Re-Run job',
        ['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'resetJob', $queuedJob->id, '?' => $query],
        ['class' => 'button warning']
    );
    echo ' ';
    echo $this->Form->postLink(
        'Remove job',
        ['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'removeJob', $queuedJob->id, '?' => $query],
        ['class' => 'button alert']
    );
}
```
The `redirect` query string element makes sure you are getting redirected back to this page (instead of Queue admin dashboard).

Make sure you allow those actions to be accessed by the user (role) that can trigger this.
Ideally, you also only display those buttons if that user has the access to do so.
[TinyAuth](https://github.com/dereuromark/cakephp-tinyauth) can be used for that, for example.


## Tips for Development


### Only pass identification data if possible

If you have larger data sets, or maybe even objects/entities, do not pass those.
They would not survive the json_encode/decode part and will maybe even exceed the text field in the database.

Instead, pass only the ID of the entity, and get your data in the Task itself.
If you have other larger chunks of data, store them somewhere and pass the path to this file.


### Using QueueTransport

Instead of manually adding job every time you want to send mail you can use existing code ond change only EmailTransport and Email configurations in `app.php`.

```php
'EmailTransport' => [
    'default' => [
        'className' => 'Smtp',
        // The following keys are used in SMTP transports
        'host' => 'host@gmail.com',
        'port' => 587,
        'timeout' => 30,
        'username' => 'username',
        'password' => 'password',
        'tls' => true,
    ],
    'queue' => [
        'className' => 'Queue.Queue',
        'transport' => 'default',
    ],
],

'Email' => [
    'default' => [
        'transport' => 'queue',
        'from' => 'no-reply@host.com',
        'charset' => 'utf-8',
        'headerCharset' => 'utf-8',
    ],
],
```

This way each time with `$mailer->deliver()` it will use `QueueTransport` as main to create job and worker will use `'transport'` setting to send mail.


#### Difference between QueueTransport and SimpleQueueTransport

* `QueueTransport` serializes whole email into the database and is useful when you have custom `Message` class.
* `SimpleQueueTransport` extracts all data from Message (to, bcc, template etc.) and then uses this to recreate Message inside task, this
is useful when dealing with emails which serialization would overflow database `data` field length.
This can only be used for non-templated emails.

### Using built in Email task

The quickest and easiest way is to use the built in Email task:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'from' => Configure::read('Config.adminEmail'),
        'subject' => $subject,
    ],
    'content' => $content,
];
$queuedJobsTable = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Email', $data);
```

This will sent a plain email. Each settings key must have a matching setter method on the Message class.
The prefix `set` will be auto-added here when calling it.

If you want a templated email, you need to pass view vars instead of content:
```php
$data = [
    'settings' => [
        'to' => $user->email,
        'from' => Configure::read('Config.adminEmail'),
        'subject' => $subject,
    ],
    'vars' => [
        'myEntity' => $myEntity,
        ...
    ],
];
 ```

You can also assemble a Mailer object manually and pass that along as settings directly:
```php
$data = [
    'settings' => $mailerObject,
    'content' => $content,
];
```

Inside a controller you can for example do this for your mailers:
```php
$mailer = $this->getMailer('User');
$mailer->viewBuilder()
    ->setTemplate('register');
$mailer->set...(...);

$this->loadModel('Queue.QueuedJobs')->createJob(
    'Email',
    ['settings' => $mailer]
);
```
Do not send your emails here, only assemble them. The Email Queue task triggers the `deliver()` method.

### Manually assembling your emails

This is the most customizable way to generate your asynchronous emails.

Don't generate them directly in your code and pass them to the queue, instead just pass the minimum requirements, like non persistent data needed and the primary keys of the records that need to be included.
So let's say someone posted a comment and you want to get notified.

Inside your CommentsTable class after saving the data you execute this hook:

```php
/**
 * @param \App\Model\Entity\Comment $comment
 * @return void
 */
protected function _notifyAdmin(Comment $comment)
{
    /** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
    $QueuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
    $data = [
        'settings' => [
            'subject' => __('New comment submitted by {0}', $comment->name)
        ],
        'vars' => [
            'comment' => $comment->toArray()
        ]
    ];
    $QueuedJobs->createJob('CommentNotification', $data);
}
```

And your `QueueAdminEmailTask::run()` method (using `MailerAwareTrait`):

```php
$this->getMailer('User');
$this->Mailer->viewBuilder()->setTemplate('comment_notification');
// ...
if (!empty($data['vars'])) {
    $this->Mailer->setViewVars($data['vars']);
}

$this->Mailer->deliver();
```

Make sure you got the template for it then, e.g.:

```php
<?= $comment->name ?> ( <?= $comment->email ?> ) wrote:

<?= $comment->message ?>

<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Comments', 'action'=> 'view', $comment['id']], true) ?>
```

This way all the generation is in the specific task and template and can be tested separately.

### Using built in Execute task
The built in task directly runs on the same path as your app, so you can use relative paths or absolute ones:
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

By default it only allows return code `0` (success) to pass. If you need different accepted return codes, pass them as "accepted" array.
If you want to disable this check and allow any return code to be successful, pass `[]` (empty array).

*Warning*: This can essentially execute anything on CLI. Make sure you never expose this directly as free-text input to anyone.
Use only predefined and safe code-snippets here!

### Multi Server Setup
When working with multiple CLI servers there are several requirements for it to work smoothly:

Make sure `env('SERVER_NAME')` or `gethostname()` return a unique name per server instance.
This is required as PID alone is now not unique anymore.
Each worker then registers itself as combination of `PID + server name`.

You can not kill workers on a different server, you can only mark them as "to be terminated".
The next run the worker registers this and auto-terminates early.

Removing PIDs from DB will yield the same result then, a "soft-killing".

### Ending workers
The "soft-killing" should be preferred over "hard-killing".
It will make sure the worker process will finish the current job and then abort right afterwards.

This is useful when deploying a code or DB migration change and you want the "old workers" based on the old code
to not process any new incoming jobs after deployment.

In this case, make sure one of your first calls of the deployment script is
```
bin/cake queue end -q
```

To avoid further deployment issues, also try to keep the runtime per worker to only a few minutes.
You can additionally do this call at the end of the deployment script to make sure any workers started in the meantime
will also be aborting early.

#### Ending workers per server
A useful feature when having multiple servers and workers, and deploying separately, is to only end the workers on the server you are deploying to.

For this make sure you have either `env('SERVER_NAME')` or `gethostname()` return a unique name per server instance (see above).
These are stored in the processes and as such you can then end them per instance that deploys.

This snippet should be in the deploy script then instead.
```
bin/cake queue end server -q
```

You can check/verify the current server name using `bin/cake queue stats`.

If you want to test locally, type `export SERVER_NAME=myserver1` and then run the above.

#### Rate limiting and throttling

The following configs can be made specific per Task, hardcoded on the class itself:
- rate (defaults to `0` = disabled)
- unique (defaults to `false` = disabled)
- costs (defaults to `0` = disabled)

Check if you need to use "rate" config (> 0) to avoid tasks being run too often/fast per worker per timeframe.
Currently you cannot rate limit it more globally however. You can use `unique` and `costs` config, however, to more globally restrict parallel runs for job types.


Note: Once any task has either "unique" or "costs" enabled, the worker has to do a pre-query to fetch the data for this.
Thus it is disabled by default for trivial use cases.

### Killing workers

First of all: Make sure you don't run workers with `workermaxruntime` and `workertimeout` of `0`.
Then they would at least not run forever, and might pile up only if you start them faster then they terminate.
That can overload the server.

#### Via tool

You can kill workers from the backend or the command line.
Make sure you have set up the workers with the same user (www-data usually) as the user that tries to kill them, or it will not work.

#### Manually

Manually killing workers can be done using `kill -15 PID`. Replace PID with the PID number (e.g. `kill -15 21212`).

To find out what queue processes are currently running, use

    ps aux | grep php

Then you can kill them gracefully with `-15` (or forcefully with `-9`, not recommended).

Locally, if you want to kill them all, usually `killapp -15 php` does the trick.
Do not run this with production ones, though.

The console kill commands are also registered here. So if you run a worker locally,
and you enter `Ctrl+C` or alike, it will also hard-kill this worker process.

### Known Limitations

#### Concurrent workers may execute the same job multiple times

If you want to use multiple workers, please double check that all jobs have a high enough timeout (>> 2x max possible execution time of a job). Currently it would otherwise risk the jobs being run multiple times!

#### Concurrent workers may execute the same job type multiple times

If you need limiting of how many times a specific job type can be run in parallel, you need to find a custom solution here.


## Generating links/URLS in CLI
When you have Queue tasks and templates that need to create URLs, make sure you followed the core documentation
on setting the `App.fullBaseUrl` config on your server.
CLI itself does not know the URL your website is running on, so this value must be configured here for the generation to work.

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
            QueuedJobTask::class
        ],
    ],
];
```

## Baking new Queue task and test
You can bake a new task and its test via
```
bin/cake bake_queue_task generate MyTaskName [-p PluginName]
```

It will generate a `QueueMyTaskNameTask` class in the right namespace.

It will not overwrite existing classes unless you explicitly force this (after prompting).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).
