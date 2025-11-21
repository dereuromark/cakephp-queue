<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MigrationQueueIndexOptimization extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('queued_jobs')
			// Main queue fetch optimization - CRITICAL
			// Covers the primary requestJob() query which filters by completed IS NULL
			// and orders by priority, age (calculated from notbefore), and id
			->addIndex(
				['completed', 'priority', 'notbefore', 'id'],
				[
					'name' => 'queue_fetch_optimization',
				],
			)
			// Cleanup queries optimization
			// Used by flushFailedJobs() which filters by fetched < threshold
			->addIndex(
				['fetched'],
				[
					'name' => 'fetched',
				],
			)
			// Stats and job type queries optimization
			// Used by getStats() and various job_task lookups
			->addIndex(
				['job_task', 'completed'],
				[
					'name' => 'job_task_completed',
				],
			)
			// Worker tracking optimization
			// Used in requestJob() for cost and unique constraints
			->addIndex(
				['workerkey'],
				[
					'name' => 'workerkey',
				],
			)
			->update();
	}

}
