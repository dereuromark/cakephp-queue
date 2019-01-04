<?php

use Phinx\Migration\AbstractMigration;

class MigrationQueueProcessesIndex extends AbstractMigration {

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
		if ($this->table('queue_processes')->hasIndex(['pid'], ['unique' => true])) {
			$this->table('queue_processes')
				->removeIndex(['pid'], ['unique' => true])
				->save();
		}

		$this->table('queue_processes')
			->addIndex(['pid', 'server'], ['unique' => true])
			->save();
	}

}
