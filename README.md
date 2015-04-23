# CakePHP 3 Queue Plugin

This `3.0` branch is for use with **CakePHP 3**.

WARNING: NOT FULLY MIGRATED YET

[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-queue.png)](https://travis-ci.org/dereuromark/cakephp-queue)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-queue/license.png)](https://packagist.org/packages/dereuromark/cakephp-queue)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-queue/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-queue)

Modified by David Yell ([davidyell](https://github.com/davidyell))
- CakePHP 3.x support

Modified by Mark Scherer ([dereuromark](https://github.com/dereuromark))
- CakePHP2.x support
- Some minor fixes
- Added crontasks (as a different approach on specific problems)
- Possible (optional) Tools Plugin dependencies for frontend access via /admin/queue
- Config key "queue" is now "Queue" ($config['Queue'][...])

Added by Christian Charukiewicz ([charukiewicz](https://github.com/charukiewicz)):
- Configuration option 'gcprop' is now 'gcprob'
- Fixed typo in README and variable name (Propability -> Probability)
- Added a few lines about createJob() usage to README
- Added comments to queue.php explaining configuration options


## Background:

This is a very simple and minimalistic job Queue (or deferred-task) system for CakePHP.

Overall functionality is inspired by systems like Gearman, Beanstalk or dropr, but without
any illusion to compete with these more advanced Systems.

The Plugin is an attempt to provide a basic, simple to use method to enable deferred job execution,
without the hassle of setting up or running an extra queue daemon, while integrating nicely into
CakePHP and also simplifying the creation of worker scripts.

### Why use deferred execution?

Deferred execution makes sense (especially in PHP) when your page wants' to execute tasks, which are not directly related to rendering the current page.
For instance, in a BBS-type system, a new users post might require the creation of multiple personalized email messages,
notifying other users of the new content.
Creating and sending these emails is completely irrelevant to the currently active user, and should not increase page response time.
Another example would be downloading, extraction and/or analyzing an external file per request of the user.
The regular solution to these problems would be to create specialized cronjobs which use specific database states to determine which action should be done.

The Queue Plugin provides a simple method to create and run such non-user-interaction-critical tasks.

While you can run multiple workers, and can (to some extent) spread these workers to different machines via a shared database,
you should seriously consider using a more advanced system for high volume/high number of workers systems.

## Installation:

* Either copy the files in this directory into APP/Plugin/Queue; or ideally install using composer and `require dereuromark/cakephp-queue`.

* Enable the plugin within your APP/Config/bootstrap.php (unless you use loadAll):

		CakePlugin::load('Queue');

* Run the following command in the cake console to create the tables:

		cake Schema create -p Queue


## Configuration:

The plugin allows some simple runtime configuration.
You may create a file called "queue.php" inside your 'APP/Config' folder (NOT the plugins config folder) to set the following values:

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
$this->loadModel('Queue.QueuedTask');
$this->QueuedTask->createJob('Email', array('to' => 'user@example.org', ...)));

// Somewhere in the model
ClassRegistry::init('Queue.QueuedTask')->createJob('Email',
	array('to' => 'user@example.org', ...)));
```

It will use your custom APP `QueueEmailTask` to send out emails via CLI.

Important: Do not forget to set your [domain](http://book.cakephp.org/2.0/en/core-utility-libraries/email.html#sending-emails-from-cli) when sending from CLI.

### Notes
`<TaskName>` may either be the complete classname (eg. QueueExample) or the shorthand without the leading "Queue" (e.g. Example).

Also note that you dont need to add the type ("Task"): `cake Queue.Queue add SpecialExample` for QueueSpecialExampleTask.

Custom tasks should be placed in APP/Console/Command/Task.
Tasks should be named `QueueSomethingTask.php` and implement a "QueueSomethingTask", keeping CakePHP naming conventions intact. Custom tasks should extend the `QueueTask` class (you will need to include this at the top of your custom task file: `App::uses('QueueTask', 'Queue.Console/Command/Task')`).

Plugin tasks go in APP/Plugin/PluginName/Console/Command/Task.

A detailed Example task can be found in /Console/Command/Task/QueueExampleTask.php inside this folder.

## Setting up the trigger cronjob
As outlined in the [book](http://book.cakephp.org/2.0/en/console-and-shells/cron-jobs.html) you can easily set up a cronjob
to start a new worker:

	*/10  *    *    *    *  cd /full/path/to/app && Console/cake Queue.Queue runworker

This would start a new worker every 10 minutes. If you configure your max life time of a worker to 15 minutes, you
got a small overlap where two workers would run simulaniously. If you lower the 10 minutes and raise the lifetime, you
get quite a few overlapping workers and thus more "parallel" processing power.
Play around with it, but just don't shoot over the top.

### TODO

* Add priority
* Cleanup and better test coverage
