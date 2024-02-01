<?php

use Phinx\Migration\AbstractMigration;

class MigrationQueueReplaceFailedWithAttempted extends AbstractMigration {

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
		$this->table('queued_jobs')
			->renameColumn('failed', 'attempts')
			->update();
		$this->query('UPDATE queued_jobs SET attempts = attempts + 1 WHERE completed IS NOT NULL');
	}

}
