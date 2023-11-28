<?php

use Phinx\Migration\AbstractMigration;

class MigrationAddIndex extends AbstractMigration {

	/**
	 * @return void
	 */
	public function change() {
		$table = $this->table('queued_jobs');
		$table
			->addIndex('completed')
			->addIndex('job_task')
			->update();
	}

}
