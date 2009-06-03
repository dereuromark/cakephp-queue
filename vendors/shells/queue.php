<?php

/**
 * @author MGriesbach@gmail.com
 * @package QueuePlugin
 * @subpackage QueuePlugin.Shells
 */
class queueShell extends Shell {
	public $uses = array(
		'Queue.QueuedTask'
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

	public function add() {

		if (count($this->args) != 1) {
			$this->out('Please call like this:');
			$this->out('       cake queue add <taskname>');
		} else {

			if (in_array($this->args[0], $this->taskNames)) {
				$this->{$this->args[0]}->add();
			} elseif (in_array('queue_' . $this->args[0], $this->taskNames)) {
				$this->{'queue_' . $this->args[0]}->add();
			} else {
				$this->out('Error: Task not Found: ' . $this->args[0]);
				$this->out('Available Tasks:');
				foreach ($this->taskNames as $loadedTask) {
					$this->out(' * ' . $loadedTask);
				}
			}
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