<?php

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\I18n\FrozenTime;
use Queue\Model\Entity\QueuedJob;

/**
 * @property \Queue\Model\Table\QueuedJobsTable $QueuedJobs
 */
class JobCommand extends Command {

	/**
	 * @var string
	 */
	protected $modelClass = 'Queue.QueuedJobs';

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue job';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->addArgument('action', [
			'help' => 'Action (view, rerun, reset, remove)',
			'required' => false,
		]);
		$parser->addArgument('id', [
			'help' => 'ID of job record, or "all" for all',
			'required' => false,
		]);

		$parser->setDescription(
			'Display, rerun, reset or remove pending jobs.',
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$action = $args->getArgument('action');
		if (!$action) {
			$io->out('Please use with [action] [ID] added.');
			$io->out('Actions are:');
			$io->out('- view: Display status of a job');
			$io->out('- rerun: Rerun a successfully run job ("all" for all)');
			$io->out('- reset: Reset a failed job ("all" for all)');
			$io->out('- flush: Remove (all) failed jobs');
			$io->out('- remove: Remove a job ("all" for truncating)');
			$io->out('- clean: Cleanup (old jobs removal)');
			$io->out();

			/** @var array<\Queue\Model\Entity\QueuedJob> $jobs */
			$jobs = $this->QueuedJobs->find()
				->select(['id', 'job_task', 'completed', 'failed'])
				->orderDesc('id')
				->limit(20)->all()->toArray();
			if ($jobs) {
				$io->out('Last jobs are:');
			} else {
				$io->out('No jobs found');
			}

			foreach ($jobs as $job) {
				$io->out('- [' . $job->id . '] ' . $job->job_task . ' (' . ($job->completed ? 'completed' : 'failed ' . $job->failed . 'x') . ')');
			}

			return static::CODE_ERROR;
		}

		if (!in_array($action, ['view', 'rerun', 'reset', 'remove', 'clean', 'flush'], true)) {
			$io->abort('No such action');
		}

		$id = $args->getArgument('id');
		if (!$id && !in_array($action, ['clean', 'flush'], true)) {
			$io->abort('Job ID must be given, or "all" used for all.');
		}
		if ($action === 'view' && $id === 'all') {
			$io->abort('"All" does not exist for view action, only works with IDs.');
		}
		if (in_array($action, ['clean', 'flush'], true) && $id) {
			$io->abort('`' . $action . '` action does not have a 2nd argument.');
		}

		if ($id === 'all') {
			$action .= 'All';

			return $this->$action($io);
		}
		if (in_array($action, ['clean', 'flush'], true)) {
			return $this->$action($io);
		}

		$queuedJob = $this->QueuedJobs->get($id);

		return $this->$action($io, $queuedJob);
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function rerunAll(ConsoleIo $io): int {
		/** @var array<\Queue\Model\Entity\QueuedJob> $queuedJobs */
		$queuedJobs = $this->QueuedJobs->find()
			->where(['completed IS NOT' => null])
			->all()
			->toArray();

		$status = static::CODE_SUCCESS;
		foreach ($queuedJobs as $queuedJob) {
			$status |= $this->rerun($io, $queuedJob);
		}

		return $status;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function resetAll(ConsoleIo $io): int {
		/** @var array<\Queue\Model\Entity\QueuedJob> $queuedJobs */
		$queuedJobs = $this->QueuedJobs->find()
			->where(['completed IS' => null])
			->all()
			->toArray();

		$status = static::CODE_SUCCESS;
		foreach ($queuedJobs as $queuedJob) {
			$status |= $this->reset($io, $queuedJob);
		}

		return $status;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function removeAll(ConsoleIo $io): int {
		/** @var int $queuedJobs */
		$queuedJobs = $this->QueuedJobs->find()
			->count();

		$this->QueuedJobs->truncate();

		$io->out($queuedJobs . ' jobs removed - database table truncated.');

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return int
	 */
	protected function rerun(ConsoleIo $io, QueuedJob $queuedJob): int {
		if (!$queuedJob->completed) {
			$io->abort('Can only rerun successfully run jobs.');
		}

		if (!$this->QueuedJobs->rerun($queuedJob->id)) {
			return static::CODE_ERROR;
		}

		$io->success('Job ' . $queuedJob->id . ' queued for rerun');

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return int
	 */
	protected function reset(ConsoleIo $io, QueuedJob $queuedJob): int {
		if ($queuedJob->completed) {
			$io->abort('Can only reset not yet finished jobs.');
		}

		if (!$this->QueuedJobs->reset($queuedJob->id, true)) {
			return static::CODE_ERROR;
		}

		$io->success('Job ' . $queuedJob->id . ' reset for rerun');

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return int
	 */
	protected function remove(ConsoleIo $io, QueuedJob $queuedJob): int {
		$this->QueuedJobs->deleteOrFail($queuedJob);

		$io->success('Job ' . $queuedJob->id . ' removed');

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param \Queue\Model\Entity\QueuedJob $queuedJob
	 *
	 * @return int
	 */
	protected function view(ConsoleIo $io, QueuedJob $queuedJob): int {
		$io->out('Task: ' . $queuedJob->job_task);
		$io->out('Reference: ' . ($queuedJob->reference ?: '-'));
		$io->out('Group: ' . ($queuedJob->job_group ?: '-'));
		$io->out('Priority: ' . $queuedJob->priority);
		$io->out('Not before: ' . ($queuedJob->notbefore ?: '-'));

		$io->out('Completed: ' . ($queuedJob->completed ?: '-'));
		if (!$queuedJob->completed) {
			$io->out('Failed: ' . ($queuedJob->failed ?: '-'));
		}
		if ($queuedJob->failed) {
			$io->out('Failure message: ' . ($queuedJob->failure_message ?: '-'));
		}

		$data = $queuedJob->data ? unserialize($queuedJob->data) : null;
		$io->out('Data: ' . Debugger::exportVar($data, 9));

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function flush(ConsoleIo $io): int {
		$result = $this->QueuedJobs->flushFailedJobs();
		$io->success('Deleted: ' . $result);

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function clean(ConsoleIo $io): int {
		if (!Configure::read('Queue.cleanuptimeout')) {
			$io->abort('You disabled cleanuptimout in config. Aborting.');
		}

		$date = (new FrozenTime())->subSeconds((int)Configure::read('Queue.cleanuptimeout'));

		$io->out('Deleting old jobs, that have finished before ' . $date);
		$result = $this->QueuedJobs->cleanOldJobs();
		$io->success('Deleted: ' . $result);

		return static::CODE_SUCCESS;
	}

}
