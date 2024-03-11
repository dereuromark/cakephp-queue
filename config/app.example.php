<?php

/**
 * This file configures default behavior for all workers
 *
 * To modify these parameters, copy this file into your own CakePHP config directory or copy the array into your existing file.
 */
use Templating\View\Icon\BootstrapIcon;

return [
	'Queue' => [
		// time (in seconds) after which a job is requeued if the worker doesn't report back
		'defaultworkertimeout' => 1800,

		// seconds of running time after which the worker will terminate (0 = unlimited)
		'workermaxruntime' => 120,

		// minimum time (in seconds) which a task remains in the database before being cleaned up.
		'cleanuptimeout' => 2592000, // 30 days

		// number of retries if a job fails or times out.
		'defaultworkerretries' => 1,

		// seconds to sleep() when no executable job is found
		'sleeptime' => 10,

		// probability in percent of a old job cleanup happening
		'gcprob' => 10,

		// set to true for multi server setup, this will affect web backend possibilities to kill/end workers
		'multiserver' => false,

		// set this to a limit that can work with your memory limits and alike, 0 => no limit
		'maxworkers' => 3,

		// instruct a Workerprocess quit when there are no more tasks for it to execute (true = exit, false = keep running)
		'exitwhennothingtodo' => false,

		// seconds of running time after which the PHP process will terminate, null uses workermaxruntime * 100
		'workertimeout' => null,

		// determine whether logging is enabled
		'log' => true,

		// set default Mailer class
		'mailerClass' => 'Cake\Mailer\Email',

		// set default datasource connection
		'connection' => null,

		// enable Search. requires friendsofcake/search
		'isSearchEnabled' => true,

		// enable Search. requires frontend assets
		'isStatisticEnabled' => false,

		// Allow workers to wake up from their "nothing to do, sleeping" state when using QueuedJobs->wakeUpWorkers().
		// This method sends a SIGUSR1 to workers to interrupt any sleep() operation like it was their time to finish.
		// This option breaks tasks expecting sleep() to always sleep for the provided duration without interrupting.
		'canInterruptSleep' => false,

		// Skip check for createJob() and if task exists
		'skipExistenceCheck' => false,

		// Additional plugins to include tasks from (if they are not already loaded anyway)
		'plugins' => [],

		// ignores task classes
		'ignoredTasks' => [],

	],
	'Icon' => [
		'sets' => [
			'bs' => BootstrapIcon::class,
		],
	],
];
