<?php

use Phinx\Migration\AbstractMigration;

class MigrationQueueProcessesServer extends AbstractMigration {

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
			->addColumn('server', 'string', [
				'length' => 90,
				'default' => null,
				'null' => true,
				'encoding' => 'utf8mb4',
				'collation' => 'utf8mb4_unicode_ci',
			])
			->update();
	}

}
