<?php

use Phinx\Migration\AbstractMigration;

class MigrationAddIndex extends AbstractMigration {

	public function change() {
		$table = $this->table('queued_jobs');
		$table
			->addIndex('completed')
			->addIndex('job_task')
			->update();
	}

}
