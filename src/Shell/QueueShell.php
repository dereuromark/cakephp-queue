<?php

namespace Queue\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;

declare(ticks = 1);

/**
 * Main shell to init and run queue workers.
 *
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class QueueShell extends Shell {

	/**
	 * Manually trigger a Finished job cleanup.
	 *
	 * @return void
	 */
	public function clean() {
		if (!Configure::read('Queue.cleanuptimeout')) {
			$this->abort('You disabled cleanuptimout in config. Aborting.');
		}

		$this->out('Deleting old jobs, that have finished before ' . date('Y-m-d H:i:s', time() - (int)Configure::read('Queue.cleanuptimeout')));
		$this->QueuedJobs->cleanOldJobs();
		$this->QueueProcesses->cleanEndedProcesses();
	}

}
