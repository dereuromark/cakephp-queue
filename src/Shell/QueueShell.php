<?php

namespace Queue\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;

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
	 * @var string
	 */
	protected $modelClass = 'Queue.QueuedJobs';

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

	/**
	 * Gracefully end running workers when deploying.
	 *
	 * Use $in
	 * - all: to end all workers on all servers
	 * - server: to end the ones on this server
	 *
	 * @param string|null $in
	 * @return void
	 */
	public function end(?string $in = null): void {
		$processes = $this->QueuedJobs->getProcesses($in === 'server');
		if (!$processes) {
			$this->out('No processes found');

			return;
		}

		$this->out(count($processes) . ' processes:');
		foreach ($processes as $process => $timestamp) {
			$this->out(' - ' . $process . ' (last run @ ' . (new FrozenTime($timestamp)) . ')');
		}

		$options = array_keys($processes);
		$options[] = 'all';
		if ($in === null) {
			$in = $this->in('Process', $options, 'all');
		}

		if ($in === 'all' || $in === 'server') {
			foreach ($processes as $process => $timestamp) {
				$this->QueuedJobs->endProcess($process);
			}

			$this->out('All ' . count($processes) . ' processes ended.');

			return;
		}

		$this->QueuedJobs->endProcess((string)$in);
	}

	/**
	 * @return void
	 */
	public function kill(): void {
		$processes = $this->QueuedJobs->getProcesses();
		if (!$processes) {
			$this->out('No processes found');

			return;
		}

		$this->out(count($processes) . ' processes:');
		foreach ($processes as $process => $timestamp) {
			$this->out(' - ' . $process . ' (last run @ ' . (new FrozenTime($timestamp)) . ')');
		}

		if (Configure::read('Queue.multiserver')) {
			$this->abort('Cannot kill by PID in multiserver environment.');
		}

		$options = array_keys($processes);
		$options[] = 'all';
		$in = $this->in('Process', $options, 'all');
		if (!$in) {
			$this->abort('No PID given.');
		}

		if ($in === 'all') {
			foreach ($processes as $process => $timestamp) {
				$this->QueuedJobs->terminateProcess($process);
			}

			return;
		}

		$this->QueuedJobs->terminateProcess($in);
	}

	/**
	 * Manually reset (failed) jobs for re-run.
	 * Careful, this should not be done while a queue task is being run.
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->out('Resetting...');

		$count = $this->QueuedJobs->reset();

		$this->success($count . ' jobs reset.');
	}

	/**
	 * Manually reset already successfully run jobs for re-run.
	 * Careful, this should not be done with non-idempotent jobs.
	 *
	 * This is mainly useful for debugging and local development,
	 * if you have to run sth again.
	 *
	 * @param string $type
	 * @param string|null $reference
	 * @return void
	 */
	public function rerun($type, $reference = null) {
		$this->out('Rerunning...');

		$count = $this->QueuedJobs->rerun($type, $reference);

		$this->success($count . ' jobs reset for re-run.');
	}

	/**
	 * Truncates the queue table
	 *
	 * @return void
	 */
	public function hardReset() {
		$this->QueuedJobs->truncate();
		$message = __d('queue', 'OK');

		$this->out($message);
	}

	/**
	 * Get option parser method to parse commandline options
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$subcommandParser = [
		];
		$subcommandParserFull = $subcommandParser;

		$rerunParser = $subcommandParser;
		$rerunParser['arguments'] = [
			'type' => [
				'help' => 'Job type. You need to specify one.',
				'required' => true,
			],
			'reference' => [
				'help' => 'Reference.',
				'required' => false,
			],
		];

		return parent::getOptionParser()
			->addSubcommand('clean', [
				'help' => 'Remove old jobs (cleanup)',
				'parser' => $subcommandParser,
			])
			->addSubcommand('reset', [
				'help' => 'Manually reset (failed) jobs for re-run.',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('rerun', [
				'help' => 'Manually rerun (successfully) run job.',
				'parser' => $rerunParser,
			])
			->addSubcommand('hard_reset', [
				'help' => 'Hard reset queue (remove all jobs)',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('end', [
				'help' => 'Manually end a worker.',
				'parser' => $subcommandParserFull,
			])
			->addSubcommand('kill', [
				'help' => 'Manually kill a worker.',
				'parser' => $subcommandParserFull,
			]);
	}

}
