# CakePHP Queue Plugin

modified by Mark Scherer (dereuromark)
- cake2.x support
- some minor fixes
- added crontasks (as a different approach on specific problems)
- possible Tools Plugin dependencies

new:
- config key "queue" is now "Queue" ($config['Queue'][...])


## Background:

This is a very simple and minimalistic job Queue (or deferred-task) system for CakePHP.

Overall functionality is inspired by systems like Gearman, Beanstalk or dropr, but without
any illusion to compete with these more advanced Systems.

The Plugin is an attempt to provide a basic, simple to use method to enable deferred job execution,
without the hassle of setting up or running an extra queue daemon, while integrating nicely into
CakePHP and also simplifying the creation of worker scripts.

### Why use deferred execution?

deferred execution makes sense (especially in PHP) when your page wants' to execute tasks, which are not directly related to rendering the current page.
For instance, in a BBS-type system, a new users post might require the creation of multiple personalized email messages,
notifying other users of the new content.
Creating and sending these emails is completely irrelevant to the currently active user, and should not increase page response time.
Another example would be downloading, extraction and/or analyzing an external file per request of the user.
The regular solution to these problems would be to create specialized cronjobs which use specific database states to determine which action should be done.

The Queue Plugin provides a simple method to create and run such non-user-interaction-critical tasks.

While you can run multiple workers, and can (to some extend) spread these workers to different machines via a shared database,
you should seriously consider using a more advanced system for high volume/high number of workers systems.


## Installation:

* Copy the files in this directory into APP/Plugin/Queue
* Enable the plugin within your APP/Config/boostrap.php (unless you loadAll)

	CakePlugin::load('Queue');

* Run the following command in the cake console to create the tables:
on Cakephp 2.x:

	@cake Schema create -p Queue@


## Configuration:

The plugin allows some simple runtime configuration.
You may create a file called "queue.php" inside your 'APP/Config' folder (NOT the plugins config folder) to set the following values:

#seconds to sleep() when no executable job is found

	$config['Queue']['sleeptime'] = 10;

#Propability in percent of a old job cleanup happening

	$config['Queue']['gcprop'] = 10;

#Default timeout after which a job is requeued if the worker doesn't report back

	$config['Queue']['defaultworkertimeout'] = 120;

#Default number of retries if a job fails or times out.

	$config['Queue']['defaultworkerretries'] = 4;

#Seconds of runnig time after which the worker will terminate (0 = unlimited)

	$config['Queue']['workermaxruntime'] = 0;

#Should a Workerprocess quit when there are no more tasks for it to execute (true = exit, false = keep running)

	$config['Queue']['exitwhennothingtodo'] = false;

You can also drop the configuration into an existing config file that is already been loaded.

The values above are the default settings which apply, when no configuration is found.


## Usage:

Run the following using the CakePHP shell:

	cake Queue.Queue

* Display Help message


	cake Queue.Queue add <taskname>

* Try to call the cli add() function on a task
* tasks may or may not provide this functionality.

	cake Queue.Queue runworker

* run a queue worker, which will look for a pending task it can execute.
* the worker will always try to find jobs matching its installed Tasks

*Notes:*
	_<taskname>_ may either be the complete classname (eg. QueueExample) or the shorthand without the leading "Queue" (eg. Example)

Also note that in 2.x you can also use CamelCase style, e.g. `cake add SpecialExample` for QueueSpecialExampleTask

Use '@cake Queue.Queue help@' to get a list of installed/available tasks.

Custom tasks should be placed in APP/Console/Command/Task.
Tasks should be named `QueueSomethingTask.php` and implement a "QueueSomethingTask", keeping CakePHP naming conventions intact.

A detailed Example task can be found in /Console/Command/Task/QueueExampleTask.php inside this folder.


### Status
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-queue.png)](https://travis-ci.org/dereuromark/cakephp-queue)
