<?php

use Phinx\Migration\AbstractMigration;

class MigrationQueueStatus extends AbstractMigration {

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
		$this->table('queue_processes')
			->addColumn('terminate', 'boolean', [
				'default' => 0,
				'null' => false,
			])
			->update();

		$this->table('queue_processes')
			->addIndex(['pid'], ['unique' => true])
			->save();
	}

}
