<?php

use Phinx\Migration\AbstractMigration;

class MigrationAddIndex extends AbstractMigration {

	/**
	 * @return void
	 */
	public function change() {
		//FIXME: make sure this is void when a migrating with `202311128071500` instead of `20231112807150` has been run already.

		$table = $this->table('queued_jobs');
		$table
			->addIndex('completed')
			->addIndex('job_task')
			->update();
	}

}
