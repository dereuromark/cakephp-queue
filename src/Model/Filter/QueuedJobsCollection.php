<?php
declare(strict_types=1);

namespace Queue\Model\Filter;

use Cake\Http\Exception\NotImplementedException;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Queue\Model\Table\QueuedJobsTable;
use Search\Model\Filter\FilterCollection;

class QueuedJobsCollection extends FilterCollection {

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this
			->value('job_task')
			->like('search', [
				'before' => true,
				'after' => true,
				'fields' => ['job_group', 'reference', 'status'],
			])
			->add('status', 'Search.Callback', [
				'callback' => function (SelectQuery $query, array $args, $filter) {
					$status = $args['status'];
					if ($status === 'completed') {
						$query->where(['completed IS NOT' => null]);

						return true;
					}
					if ($status === 'in_progress') {
						$query->where([
							'completed IS' => null,
							// Exclude terminally-failed (aborted) jobs: they are
							// done, not in progress, even without a completed stamp.
							'OR' => [
								'status IS' => null,
								'status !=' => QueuedJobsTable::STATUS_ABORTED,
							],
							'AND' => [
								'OR' => [
									'notbefore <=' => new DateTime(),
									'notbefore IS' => null,
								],
							],
						]);

						return true;
					}
					if ($status === 'scheduled') {
						$query->where(['completed IS' => null, 'notbefore >' => new DateTime()]);

						return true;
					}
					if ($status === 'pending') {
						// Waiting to be picked up: never fetched, no failure, due,
						// and not aborted. Mirrors the dashboard "Pending" card
						// (totalPending minus running and retriable-failed).
						$query->where([
							'completed IS' => null,
							'fetched IS' => null,
							'failure_message IS' => null,
							'AND' => [
								[
									'OR' => [
										'notbefore <=' => new DateTime(),
										'notbefore IS' => null,
									],
								],
								[
									'OR' => [
										'status IS' => null,
										'status !=' => QueuedJobsTable::STATUS_ABORTED,
									],
								],
							],
						]);

						return true;
					}
					if ($status === 'running') {
						// Picked up by a worker and not yet completed or failed.
						$query->where([
							'completed IS' => null,
							'fetched IS NOT' => null,
							'failure_message IS' => null,
						]);

						return true;
					}
					if ($status === 'failed') {
						// Unfinished jobs with a recorded failure: still-retrying
						// ones and terminally aborted ones alike.
						$query->where(['completed IS' => null, 'failure_message IS NOT' => null]);

						return true;
					}
					if ($status === 'aborted') {
						// Terminally failed: retries exhausted, will never run again.
						$query->where(['completed IS' => null, 'status' => QueuedJobsTable::STATUS_ABORTED]);

						return true;
					}

					throw new NotImplementedException('Invalid status type');
				},
			]);
	}

}
