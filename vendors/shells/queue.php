<?php

/**
 * @copyright esolut GmbH 2009
 * @link http://www.esolut.de
 * @author mgr2
 * @package QueuePlugin
 * @subpackage QueuePlugin.Shells
 * @version $Id:  $
 */
class queueShell extends Shell {
	public $uses = array(
		'QueuedTask'
	);
	/**
	 * Codecomplete Hint
	 *
	 * @var QueuedTask
	 */
	public $QueuedTask;

	/**
	 * Overwrite shell initialize to dynamically load all Queue Related Tasks.
	 */
	public function initialize() {
		App::import('Folder');
		$this->_loadModels();

		foreach ($this->Dispatch->shellPaths as $path) {
			$folder = new Folder($path . DS . 'tasks');
			$this->tasks = array_merge($this->tasks, $folder->find('queue_.*\.php'));
		}
		// strip the extension fom the found task(file)s
		foreach ($this->tasks as &$task) {
			$task = basename($task, '.php');
		}
	}

	public function help() {
		$this->out('Available Tasks:');
		foreach ($this->taskNames as $loadedTask) {
			$this->out(' * ' . $loadedTask);
		}

	}

	public function runworker() {
		while (true) {
			$this->out('Fetching Job....');
			$data = $this->QueuedTask->requestJob($this->tasks);
			if ($data != false) {
				$this->out('Running job of type "' . $data['jobtype'] . '"');
				$return = $this->$data['jobtype']->run(unserialize($data['data']));
				if ($return == true) {
					$this->QueuedTask->markJobDone($data['id']);
					$this->out('Job Finished.');
				} else {
					$this->QueuedTask->markJobFailed($data['id']);
					$this->out('Job did not finish, requeued.');
				}
			} else {
				$this->out('nothing to do, sleeping.');
				sleep(10);
			}

			if (rand(0, 1000) > 990) {
				$this->out('Performing Old job cleanup.');
				$this->QueuedTask->cleanOldJobs();
			}
			$this->hr();
		}
	}

	public function test() {
		debug($this->QueuedTask->getTypes());

	}
}
?>