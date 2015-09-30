<?php

use Phinx\Migration\AbstractMigration;

class Fixmissing extends AbstractMigration {

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
		$table->addColumn('status', 'string', ['limit' => 255])
			->renameColumn('group', 'task_group')
			->update();
	}

}
