<?php

use Phinx\Migration\AbstractMigration;

class Priority extends AbstractMigration {

	/**
	 * Change Method.
	 *
	 * Write your reversible migrations using this method.
	 *
	 * More information on writing migrations is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
	 *
	 * @return void
	 */
	public function change() {
		$table = $this->table('queued_tasks');
		$table->rename('queued_jobs')
			->renameColumn('job_group', 'job_group')
			->renameColumn('job_type', 'job_type')
			->update();
	}

}
