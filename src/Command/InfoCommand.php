<?php
declare(strict_types=1);

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
 */
class InfoCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Show queue status and available tasks.';
	}

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = 'Queue.QueuedJobs';

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

		$parser->addOption('format', [
			'help' => 'Output format. `json` prints job counts, workers and statistics for monitoring (no settings).',
			'choices' => ['text', 'json'],
			'default' => 'text',
		]);
		$parser->setDescription(
			'Get list of available tasks as well as current settings and statistics.',
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 *
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		if ($args->getOption('format') === 'json') {
			$io->out((string)json_encode($this->jsonInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			return static::CODE_SUCCESS;
		}

		$tasks = $this->getTasks();
		$addableTasks = $this->getAddableTasks();

		$io->out(count($tasks) . ' tasks available:');
		foreach (array_keys($tasks) as $task) {
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

		// Map of old config names to new names for display
		$configMapping = [
			'defaultworkertimeout' => 'defaultRequeueTimeout',
			'workermaxruntime' => 'workerLifetime',
			'defaultworkerretries' => 'defaultJobRetries',
			'workertimeout' => 'workerPhpTimeout',
		];

		foreach ($conf as $key => $val) {
			if ($val === false) {
				$val = 'no';
			}
			if ($val === true) {
				$val = 'yes';
			}

			// Display new config name if it exists, otherwise use the original
			$displayKey = $key;
			if (isset($configMapping[$key])) {
				$displayKey = $configMapping[$key] . ' (was: ' . $key . ')';
			}

			$io->out('* ' . $displayKey . ': ' . print_r($val, true));
		}

		$io->out();
		$io->hr();
		$io->out();

		/** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
		$QueuedJobs = $this->fetchTable('Queue.QueuedJobs');
		$QueueProcesses = $this->fetchTable('Queue.QueueProcesses');

		$io->out('Total unfinished jobs: ' . $QueuedJobs->getLength());
		$status = $QueueProcesses->status();
		$io->out('Current running workers: ' . ($status ? $status['workers'] : '-'));
		$io->out('Last run: ' . ($status ? $status['time']->nice() : '-'));
		$io->out('Server name: ' . $QueueProcesses->buildServerString());

		$io->out();
		$io->hr();
		$io->out();

		$io->out('Jobs currently in the queue:');
		/** @var array<string, string> $types */
		$types = $QueuedJobs->getTypes()->toArray();
		//TODO: refactor using $io->helper table?
		foreach ($types as $type) {
			$io->out(' - ' . str_pad($type, 20, ' ', STR_PAD_RIGHT) . ': ' . $QueuedJobs->getLength($type));
		}

		$io->out();

		$scheduled = $QueuedJobs->getScheduledStats()->count();
		$io->out('Jobs currently scheduled to run in the future: ' . $scheduled);
		$io->out('Aborted jobs (retries exhausted): ' . $QueuedJobs->getAbortedCount());

		$io->out();
		$io->hr();
		$io->out();

		$io->out('Finished job statistics:');
		$data = $QueuedJobs->getStats();
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
	 * Settings are left out: they can hold closures and credentials.
	 *
	 * @return array<string, mixed>
	 */
	protected function jsonInfo(): array {
		/** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
		$QueuedJobs = $this->fetchTable('Queue.QueuedJobs');
		/** @var \Queue\Model\Table\QueueProcessesTable $QueueProcesses */
		$QueueProcesses = $this->fetchTable('Queue.QueueProcesses');
		$status = $QueueProcesses->status();

		$queuedByTask = [];
		/** @var array<string, string> $types */
		$types = $QueuedJobs->getTypes()->toArray();
		foreach ($types as $type) {
			$queuedByTask[$type] = $QueuedJobs->getLength($type);
		}

		$finished = [];
		foreach ($QueuedJobs->getStats() as $item) {
			$finished[$item['job_task']] = [
				'count' => (int)$item['num'],
				'avgExistence' => (float)$item['alltime'],
				'avgDelay' => (float)$item['fetchdelay'],
				'avgRuntime' => (float)$item['runtime'],
			];
		}

		return [
			'server' => $QueueProcesses->buildServerString(),
			'workers' => $status ? $status['workers'] : 0,
			'lastRun' => $status ? $status['time']->toIso8601String() : null,
			'jobs' => [
				'unfinished' => $QueuedJobs->getLength(),
				'pending' => $QueuedJobs->getPendingCount(),
				'scheduled' => $QueuedJobs->getScheduledCount(),
				'aborted' => $QueuedJobs->getAbortedCount(),
			],
			'queuedByTask' => $queuedByTask,
			'finished' => $finished,
			'tasks' => array_keys($this->getTasks()),
		];
	}

	/**
	 * @return array<string>
	 */
	protected function getTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->all();
	}

	/**
	 * @return array<string>
	 */
	protected function getAddableTasks(): array {
		$taskFinder = new TaskFinder();

		return $taskFinder->allAddable();
	}

}
