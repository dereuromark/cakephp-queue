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
```php
bin/cake Migrations migrate -p Queue
```

## Configuration:

The plugin allows some simple runtime configuration.
You may create a file called `app_queue.php` inside your `config` folder (NOT the plugins config folder) to set the following values:

- Seconds to sleep() when no executable job is found:

		$config['Queue']['sleeptime'] = 10;

- Probability in percent of an old job cleanup happening:

		$config['Queue']['gcprob'] = 10;

- Default timeout after which a job is requeued if the worker doesn't report back:

		$config['Queue']['defaultworkertimeout'] = 3600;

- Default number of retries if a job fails or times out:

		$config['Queue']['defaultworkerretries'] = 3;

- Seconds of running time after which the worker will terminate (0 = unlimited):

		$config['Queue']['workermaxruntime'] = 60;

	Warning: Do not use 0 if you are using a cronjob to permanantly start a new worker once in a while and if you do not exit on idle.

- Should a Workerprocess quit when there are no more tasks for it to execute (true = exit, false = keep running):

		$config['Queue']['exitwhennothingtodo'] = false;

- Minimum number of seconds before a cleanup run will remove a completed task; defaults to 0 for the Queue worker, or 2592000 for the Cron worker:

		$config['Queue']['cleanuptimeout'] = 2592000; // 30 days

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

## Usage:

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

### Updating status
The createJob() method returns the entity. So you can store the ID and at any time ask the queue about the status of this job.
```php
// Inside your website
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

## Tips for Development

### Only pass identification data if possible
If you have larger data sets, or maybe even objects/entities, do not pass those.
They would not survive the json_encode/decode part and will maybe even exceed the text field in the database.

Instead, pass only the ID of the entity, and get your data in the Task itself.
If you have other larger chunks of data, store them somewhere and pass the path to this file.


### Using QueueTransport
Instead of manually adding job every time you want to send mail you can use existing code ond change only EmailTransport and Email configurations in `app.php`.
```PHP
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
	...
	if (!empty($data['vars'])) {
		$this->Email->viewVars($data['vars']);
	}

	return (bool)$this->Email->send();
```

Make sure you got the template for it then, e.g.:
```
<?php echo $comment['name'] ?> ( <?php echo $comment['email']; ?> ) wrote:

<?php echo $comment['message']; ?>


<?php echo $this->Url->build(['prefix' => 'admin', 'controller' => 'Comments', 'action'=> 'view', $comment['id']], true); ?>
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
