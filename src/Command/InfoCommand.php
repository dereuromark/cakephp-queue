<?php

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\I18n\Number;
use Queue\Queue\TaskFinder;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class InfoCommand extends Command {

	/**
	 * @var string
	 */
	protected $modelClass = 'Queue.QueuedJobs';

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue info';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->setDescription(
			'Get list of available tasks as well as current settings and statistics.'
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$tasks = $this->getTasks();
		$addableTasks = $this->getAddableTasks();

		$io->out(count($tasks) . ' tasks available:');
		foreach ($tasks as $task => $className) {
			if (array_key_exists($task, $addableTasks)) {
				$task .= ' <info>[addable via CLI]</info>';
			}
			$io->out(' * ' . $task);
		}

		$io->out();
		$io->hr();
		$io->out();

		$io->out('Current Settings:');
		$conf = (array)Configure::read('Queue');
		foreach ($conf as $key => $val) {
			if ($val === false) {
				$val = 'no';
			}
			if ($val === true) {
				$val = 'yes';
			}
			$io->out('* ' . $key . ': ' . print_r($val, true));
		}

		$io->out();
		$io->hr();
		$io->out();

		$io->out('Total unfinished jobs: ' . $this->QueuedJobs->getLength());
		$this->loadModel('Queue.QueueProcesses');
		$status = $this->QueueProcesses->status();
		$io->out('Current running workers: ' . ($status ? $status['workers'] : '-'));
		$io->out('Last run: ' . ($status ? $status['time']->nice() : '-'));
		$io->out('Server name: ' . $this->QueueProcesses->buildServerString());

		$io->out();
		$io->hr();
		$io->out();

		$io->out('Jobs currently in the queue:');
		$types = $this->QueuedJobs->getTypes()->toArray();
		//TODO: refactor using $io->helper table?
		foreach ($types as $type) {
			$io->out(' - ' . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ': ' . $this->QueuedJobs->getLength($type));
		}

		$io->out();
		$io->hr();
		$io->out();

		$io->out('Finished job statistics:');
		$data = $this->QueuedJobs->getStats();
		//TODO: refactor using $io->helper table?
		foreach ($data as $item) {
			$io->out(' - ' . $item['job_task'] . ': ');
			$io->out('   - Finished Jobs in Database: ' . $item['num']);
			$io->out('   - Average Job existence    : ' . str_pad(Number::precision($item['alltime'], 0), 8, ' ', STR_PAD_LEFT) . 's');
			$io->out('   - Average Execution delay  : ' . str_pad(Number::precision($item['fetchdelay'], 0), 8, ' ', STR_PAD_LEFT) . 's');
			$io->out('   - Average Execution time   : ' . str_pad(Number::precision($item['runtime'], 0), 8, ' ', STR_PAD_LEFT) . 's');
		}
	}

	/**
	 * @return string[]
	 */
	protected function getTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->all();
	}

	/**
	 * @return string[]
	 */
	protected function getAddableTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->allAddable();
	}

}
