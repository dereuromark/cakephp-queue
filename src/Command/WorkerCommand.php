<?php
declare(strict_types=1);

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Queue\Model\Table\QueueProcessesTable;
use Queue\Queue\Config;

/**
 * @property \Queue\Model\Table\QueueProcessesTable $QueueProcesses
 */
class WorkerCommand extends Command {

	protected QueueProcessesTable $QueueProcesses;

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = 'Queue.QueueProcesses';

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this->QueueProcesses = $this->fetchTable('Queue.QueueProcesses');
	}

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue worker';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->addArgument('action', [
			'help' => 'Action (end, kill, maintenance)',
			'required' => false,
		]);
		$parser->addArgument('pid', [
			'help' => 'PID (Process/Worker ID)',
			'required' => false,
		]);

		$parser->setDescription(
			'Display, end or kill running workers.',
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
		$action = $args->getArgument('action');
		if (!$action) {
			$io->out('Please use with [action] [PID] added.');
			$io->out('Actions are:');
			$io->out('- end: Gracefully end a worker/process, use "all"/"server" for all');
			$io->out('- kill: Kill a worker/process, use "all"/"server" for all');
			$io->out('- clean: ');
			$io->out();

			/** @var array<\Queue\Model\Entity\QueueProcess> $processes */
			$processes = $this->QueueProcesses->find()
				->orderByDesc('modified')
				->limit(10)->all()->toArray();
			if ($processes) {
				$io->out('Last jobs are:');
			} else {
				$io->out('No workers/processes found');
			}

			foreach ($processes as $worker) {
				$io->out('- [' . $worker->pid . '] ' . $worker->server . ':' . $worker->workerkey . ' (' . ($worker->terminate ? 'scheduled to terminate' : 'running') . ')');
				$io->out('  Last run: ' . $worker->modified);
			}

			return static::CODE_ERROR;
		}

		if (!in_array($action, ['end', 'kill', 'clean', 'clear'], true)) {
			$io->abort('No such action');
		}
		$pid = $args->getArgument('pid');
		if (!$pid && $action !== 'clean' && $action !== 'clear') {
			$io->abort('PID must be given, or "all" used for all.');
		}
		if (($action === 'clean' || $action === 'clear') && $pid) {
			$io->abort('Clean action does not have a 2nd argument.');
		}

		return $this->$action($io, $pid);
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $pid
	 *
	 * @return int
	 */
	protected function end(ConsoleIo $io, string $pid): int {
		if ($pid === 'all' || $pid === 'server') {
			$workers = $this->QueueProcesses->getProcesses($pid === 'server');
			foreach ($workers as $worker) {
				$this->QueueProcesses->endProcess($worker->pid);
				$io->success('Job ' . $worker->pid . ' marked for termination (will finish current job)');
			}

			return static::CODE_SUCCESS;
		}

		/** @var \Queue\Model\Entity\QueueProcess $worker */
		$worker = $this->QueueProcesses->find()->where(['pid' => $pid])->first();
		if (!$worker) {
			$io->abort('No such worker/process (anymore).');
		}

		$this->QueueProcesses->endProcess($worker->pid);

		$io->success('Job ' . $worker->pid . ' marked for termination (will finish current job)');

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $pid
	 *
	 * @return int
	 */
	protected function kill(ConsoleIo $io, string $pid): int {
		if ($pid === 'all' || $pid === 'server') {
			$workers = $this->QueueProcesses->getProcesses($pid === 'server');
			foreach ($workers as $worker) {
				if ($pid === 'all' && Configure::read('Queue.multiserver')) {
					$serverString = $this->QueueProcesses->buildServerString();
					if ($serverString !== $worker->workerkey) {
						$io->abort('Cannot kill by PID in multiserver environment for this CLI. You need to execute this on the same server.');
					}
				}
				$this->QueueProcesses->terminateProcess($worker->pid);
				$io->success('Job ' . $worker->pid . ' killed');
			}

			return static::CODE_SUCCESS;
		}

		/** @var \Queue\Model\Entity\QueueProcess $worker */
		$worker = $this->QueueProcesses->find()->where(['pid' => $pid])->first();
		if (!$worker) {
			$io->abort('No such worker/process (anymore).');
		}

		if (Configure::read('Queue.multiserver')) {
			$serverString = $this->QueueProcesses->buildServerString();
			if ($serverString !== $worker->workerkey) {
				$io->abort('Cannot kill by PID in multiserver environment for this CLI. You need to execute this on the same server.');
			}
		}

		$this->QueueProcesses->terminateProcess($worker->pid);

		$io->success('Job ' . $worker->pid . ' killed');

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function clean(ConsoleIo $io): int {
		$timeout = Config::defaultworkertimeout();
		if (!$timeout) {
			$io->abort('You disabled `defaultworkertimeout` in config. Aborting.');
		}
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		$io->out('Deleting old/outdated processes, that have finished before ' . $thresholdTime);
		$result = $this->QueueProcesses->cleanEndedProcesses();
		$io->success('Deleted: ' . $result);

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function clear(ConsoleIo $io): int {
		$timeout = Config::defaultworkertimeout();
		if (!$timeout) {
			$io->abort('You disabled `defaultworkertimeout` in config. Aborting.');
		}
		$thresholdTime = (new DateTime())->subSeconds($timeout);

		$io->out('Deleting processes without a PID or that have finished before ' . $thresholdTime);
		$result = $this->QueueProcesses->clearProcesses();
		$io->success('Deleted: ' . $result);

		return static::CODE_SUCCESS;
	}

}
