# CakePHP Queue Plugin Documentation


## Installation
```
composer require dereuromark/cakephp-queue
```

Enable the plugin within your config/bootstrap.php (unless you use loadAll):
```php
Plugin::load('Queue');
```
If you want to also access the backend controller (not just using CLI), you need to use
```php
Plugin::load('Queue', ['routes' => true]);
```

Run the following command in the CakePHP console to create the tables using the Migrations plugin:
```sh
bin/cake Migrations migrate -p Queue
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
    $config['Queue']['defaultworkertimeout'] = 3600;
    ```

- Default number of retries if a job fails or times out:

    ```php
    $config['Queue']['defaultworkerretries'] = 3;
    ```

- Seconds of running time after which the worker will terminate (0 = unlimited):

    ```php
    $config['Queue']['workermaxruntime'] = 60;
    ```

    *Warning:* Do not use 0 if you are using a cronjob to permanantly start a new worker once in a while and if you do not exit on idle.

- Should a Workerprocess quit when there are no more tasks for it to execute (true = exit, false = keep running):

    ```php
    $config['Queue']['exitwhennothingtodo'] = false;
    ```

- Minimum number of seconds before a cleanup run will remove a completed task (set to 0 to disable):

    ```php
    $config['Queue']['cleanuptimeout'] = 2592000; // 30 days
    ```

- Use a different connection:

    ```php
    $config['Queue']['connection'] = 'custom'; // Defaults to 'default'
    ```

Don't forget to load that config file with `Configure::load('app_queue');` in your bootstrap.
You can also use `Plugin::load('Queue', ['bootstrap' => true]);` which will load your `app_queue.php` config file automatically.

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

Finally, make sure you allow the configured `pidfilepath` to be creatable and writable.
Especially on deployment some `mkdir` command might be necessary.
Set it to false to use the DB here instead, as well.

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
I recommend setting it to at least 2x the maximum possible execution length.

Set the retries to at least 1, otherwise it will never execute again after failure in the first run.

## Writing your own task

In most cases you wouldn't want to use the existing task, but just quickly build your own.
Put it into `/src/Shell/Task/` as `Queue{YourNameForIt}Task.php`.

You need to at least implement the run method:
```php
namespace App\Shell\Task;

...

class QueueYourNameForItTask extends QueueTask {

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
	 * @return bool Success
	 */
	public function run(array $data, $jobId) {
		$this->loadModel('FooBars');
		if (!$this->FooBars->doSth()) {
			throw new RuntimeException('Couldnt do sth.');
		}

		return true;
	}
	
}
```
Make sure it returns a boolean result (true ideally), or otherwise throws an exception with a clear error message.

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


Some tasks will not be triggered from the console, but from the APP code.
You will need to use the model access for QueueTask and the createJob() function to do this.

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
TableRegistry::get('Queue.QueuedJobs')->createJob('Email',
    ['to' => 'user@example.org', ...]);
```

It will use your custom APP `QueueEmailTask` to send out emails via CLI.

Important: Do not forget to set your [domain](https://book.cakephp.org/3.0/en/core-libraries/email.html#sending-emails-from-cli) when sending from CLI.

### Avoiding parallel (re)queueing

For some background-tasks you will want to make sure only a single instance of this type is currently run. 
In your logic you can check on this using `isQueued()` and a unique reference:
```php
    /**
     * @return \Cake\Http\Response|null
     */
    public function triggerImport()
    {
        $this->request->allowMethod('post');

        $this->loadModel('Queue.QueuedJobs');
        if ($this->QueuedJobs->isQueued('my-import')) {
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

### Updating status

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

// Get progress status in web site
$job = $this->QueuedJobs->get($id);
$progress = $job->progress; // A float from 0 to 1
echo number_format($progress * 100, 0) . '%'; // Outputs 87% for example
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


## Admin backend

The plugin works completely without it, by just using the CLI shell commands.
But if you want to browse the statistics via URL, you can enable the routing for it (see above) and then access `/admin/queue`
to see how status of your queue, statistics and settings.
Please note that this requires the Tools plugin to be loaded if you do not customize the view templates on project level.
Also make sure you loaded the helpers needed (Tools.Format, Tools.Time as Time, etc).

By default the templates should work fine in both Foundation (v5+) and Boostrap (v3+).
Copy-and-paste to project level for any customization here.


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
            //'client' => null,
            'tls' => true,
        ],
        'queue' => [
            'className' => 'Queue.Queue',
            'transport' => 'default'
        ]
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

This way each time you will `$email->send()` it will use `QueueTransport` as main to create job and worker will use `'transport'` setting to send mail.


#### Difference between QueueTransport and SimpleQueueTransport

* `QueueTransport` serializes whole email into the database and is useful when you have custom `Email` class.
* `SimpleQueueTransport` extracts all data from email (to, bcc, template etc.) and then uses this to recreate email inside task, this
is useful when dealing with emails which serialization would overflow database `data` field length.


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
$queuedJobsTable = TableRegistry::get('Queue.QueuedJobs');
$queuedJobsTable->createJob('Email', $data);
```

This will sent a plain email. Each settings key must have a matching setter method on the Email class.

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
 
You can also assemble an Email object manually and pass that along as settings directly:
```php
$data = [
	'settings' => $emailObject,
	'content' => $content,
];
```

### Manually assembling your emails

This is the most advised way to generate your asynchronous emails.

Don't generate them directly in your code and pass them to the queue, instead just pass the minimum requirements, like non persistent data needed and the primary keys of the records that need to be included.
So let's say someone posted a comment and you want to get notified.

Inside your CommentsTable class after saving the data you execute this hook:

```php
/**
 * @param Comment $comment
 * @return void
 */
protected function _notifyAdmin(Comment $comment)
{
    /** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
    $QueuedJobs = TableRegistry::get('Queue.QueuedJobs');
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

And your `QueueAdminEmailTask::run()` method:

```php
$this->Email = new Email();
$this->Email->template('comment_notification');
// ...
if (!empty($data['vars'])) {
    $this->Email->viewVars($data['vars']);
}

return (bool)$this->Email->send();
```

Make sure you got the template for it then, e.g.:

```php
<?= $comment['name'] ?> ( <?= $comment['email'] ?> ) wrote:

<?= $comment['message'] ?>

<?= $this->Url->build(['prefix' => 'admin', 'controller' => 'Comments', 'action'=> 'view', $comment['id']], true) ?>
```

This way all the generation is in the specific task and template and can be tested separaretly.


### Killing workers

First of all: Make sure you don't run workers with `workermaxruntime` of `0`.
Then they would at least not run forever, and might pile up only if you start them faster then they terminate.


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


### Known Limitations


#### Concurrent workers may execute the same job multiple times


If you want to use multiple workers, please use only one per type or double check that all jobs have a high enough timeout (>> 2x max possible execution time of a job). Currently it would otherwise risk the jobs being run multiple times!


## IDE support

With [IdeHelper](https://github.com/dereuromark/cakephp-ide-helper/) plugin you can get typehinting and autocomplete for your createJob() calls.
Especially if you use PHPStorm, this will make it possible to get support here.

Include that plugin, set up your generator config and run e.g. `bin/cake phpstorm generate`.

If you use `Plugin::load('Queue', ['bootstrap' => true, ...])`, the necessary config is already auto-included (recommended).
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


## Contributing

I am looking forward to your contributions.

There are a few guidelines that I need contributors to follow:
* Coding standards (`composer cs-check` to check and `composer cs-fix` to fix)
* Passing tests (`php phpunit.phar`)
