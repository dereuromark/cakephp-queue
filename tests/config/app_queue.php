<?php

return [
	'Queue' => [
		// time (in seconds) after which a job is requeued if the worker doesn't report back
		'defaultRequeueTimeout' => 1800,

		// seconds of running time after which the worker will terminate (0 = unlimited)
		'workerLifetime' => 120,

		// minimum time (in seconds) which a task remains in the database before being cleaned up.
		'cleanuptimeout' => 2592000, // 30 days

		/* Optional */

		'isSearchEnabled' => true,
	],
];
