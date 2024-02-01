<?php

use Phinx\Migration\AbstractMigration;

class MigrationAddIndex extends AbstractMigration {

	/**
	 * @return void
	 */
	public function change() {
		// Shim: make sure this is void when a migrating with `202311128071500` instead of `20231112807150` has been run already.
		$result = $this->query('SELECT * FROM queue_phinxlog WHERE version = \'202311128071500\' LIMIT 1')->fetch();
		if ($result) {
			$this->execute('DELETE FROM queue_phinxlog WHERE version = \'202311128071500\' LIMIT 1');

			return;
		}

		$table = $this->table('queued_jobs');
		$table
			->addIndex('completed')
			->addIndex('job_task')
			->update();
	}

}
