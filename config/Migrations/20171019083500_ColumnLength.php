<?php

use Phinx\Migration\AbstractMigration;

class ColumnLength extends AbstractMigration {

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
		$table = $this->table('queue_processes');

		$table->changeColumn('pid', 'string', [
			'length' => 40,
			'null' => false,
			'default' => null,
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
		]);

		$table->save();
	}

}
