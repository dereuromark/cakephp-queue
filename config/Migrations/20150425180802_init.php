<?php

use Phinx\Migration\AbstractMigration;

class Init extends AbstractMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-change-method
	 *
	 * Uncomment this method if you would like to use it.
	 *
	 * @return void
	 */
	public function change() {
		$table = $this->table('queued_tasks');
		$table->addColumn('jobtype', 'string', ['length' => 45])
			->addColumn('data', 'text', ['null' => true])
			->addColumn('group', 'string', ['length' => 255, 'null' => true, 'default' => null])
			->addColumn('reference', 'string', ['length' => 255, 'null' => true, 'default' => null])
			->addColumn('created', 'datetime', ['null' => true, 'default' => null])
			->addColumn('notbefore', 'datetime', ['null' => true, 'default' => null])
			->addColumn('fetched', 'datetime', ['null' => true, 'default' => null])
			->addColumn('completed', 'datetime', ['null' => true, 'default' => null])
			->addColumn('progress', 'float', ['null' => true])
			->addColumn('failed', 'integer', ['default' => 0])
			->addColumn('failure_message', 'text', ['null' => true])
			->addColumn('workerkey', 'string', ['length' => 45, 'null' => true, 'default' => null])
			->create();
	}

}
