# CakePHP Queue Plugin Documentation


## Installation
```
composer require dereuromark/cakephp-queue:dev-master
```

* Enable the plugin within your config/bootstrap.php (unless you use loadAll):

		Plugin::load('Queue');

* Run the following command in the CakePHP console to create the tables using the Migrations plugin:

		cake Migrations migrate -p Queue


## Configuration:

The plugin allows some simple runtime configuration.
You may create a file called `app_queue.php` inside your `config` folder (NOT the plugins config folder) to set the following values:

- Seconds to sleep() when no executable job is found:

		$config['Queue']['sleeptime'] = 10;

- Probability in percent of an old job cleanup happening:

		$config['Queue']['gcprob'] = 10;

- Default timeout after which a job is requeued if the worker doesn't report back:

		$config['Queue']['defaultworkertimeout'] = 120;

- Default number of retries if a job fails or times out:

		$config['Queue']['defaultworkerretries'] = 4;

- Seconds of running time after which the worker will terminate (0 = unlimited):

		$config['Queue']['workermaxruntime'] = 0;

	Warning: Do not use 0 if you are using a cronjob to permanantly start a new worker once in a while and if you do not exit on idle.

- Should a Workerprocess quit when there are no more tasks for it to execute (true = exit, false = keep running):

		$config['Queue']['exitwhennothingtodo'] = false;

- Minimum number of seconds before a cleanup run will remove a completed task; defaults to 0 for the Queue worker, or 2592000 for the Cron worker:

		$config['Queue']['cleanuptimeout'] = 2592000; // 30 days

Don't forget to load that config file: `Configure::load('app_queue');`

Example `app_queue.php`:

```php
return [
	'Queue' => [
		'workermaxruntime' => 60,
		'sleeptime' => 15,
	],
];
```

You can also drop the configuration into an existing config file that is already been loaded.
The values above are the default settings which apply, when no configuration is found.


## Usage:

Run the following using the CakePHP shell:

* Display Help message:

		cake Queue.Queue

* Try to call the cli add() function on a task:

		cake Queue.Queue add <TaskName>

	Tasks may or may not provide this functionality.

* Run a queue worker, which will look for a pending task it can execute:

		cake Queue.Queue runworker

	The worker will always try to find jobs matching its installed Tasks.


Some tasks will not be triggered from the console, but from the APP code.
You will need to use the model access for QueueTask and the createJob() function to do this.

The `createJob()` function takes two arguments.  The first argument is the name of the type of job that you are creating.  The second argument can take any format and will be passed as a parameter to the `run()` function of the worker.

For sending emails, for example:

```php
// In your controller
$this->loadModel('Queue.QueuedTasks');
$this->QueuedTasks->createJob('Email', array('to' => 'user@example.org', ...)));

// Somewhere in the model
TableRegistry::get('Queue.QueuedTasks')->createJob('Email',
	array('to' => 'user@example.org', ...)));
```

It will use your custom APP `QueueEmailTask` to send out emails via CLI.

Important: Do not forget to set your [domain](http://book.cakephp.org/2.0/en/core-utility-libraries/email.html#sending-emails-from-cli) when sending from CLI.

### Notes
`<TaskName>` may either be the complete classname (eg. QueueExample) or the shorthand without the leading "Queue" (e.g. Example).

Also note that you dont need to add the type ("Task"): `cake Queue.Queue add SpecialExample` for QueueSpecialExampleTask.

Custom tasks should be placed in src/Shell/Task.
Tasks should be named `QueueSomethingTask.php` and implement a "QueueSomethingTask", keeping CakePHP naming conventions intact. Custom tasks should extend the `QueueTask` class (you will need to include this at the top of your custom task file: `use Queue\Shell\Task\QueueTask;`).

Plugin tasks go in plugins/PluginName/src/Shell/Task.

A detailed Example task can be found in src/Shell/Task/QueueExampleTask.php inside this folder.

If you copy an example, do not forget to adapt the namespace!

## Setting up the trigger cronjob
As outlined in the [book](http://book.cakephp.org/3.0/en/console-and-shells/cron-jobs.html) you can easily set up a cronjob
to start a new worker:

	*/10  *    *    *    *  cd /full/path/to/app && bin/cake Queue.Queue runworker

This would start a new worker every 10 minutes. If you configure your max life time of a worker to 15 minutes, you
got a small overlap where two workers would run simultaneously. If you lower the 10 minutes and raise the lifetime, you
get quite a few overlapping workers and thus more "parallel" processing power.
Play around with it, but just don't shoot over the top.


## Tips for Development

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

### Killing workers
//TODO

