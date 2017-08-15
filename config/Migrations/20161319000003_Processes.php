<?php

use Phinx\Migration\AbstractMigration;

class Processes extends AbstractMigration {

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
		$table
			->addColumn('pid', 'string', ['null' => false, 'default' => null, 'length' => 30])
			->addColumn('created', 'datetime', ['null' => true, 'default' => null])
			->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
			->save();
	}

}
