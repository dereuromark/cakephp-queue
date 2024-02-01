<?php

use Phinx\Migration\AbstractMigration;

class QueueProcessesAddInfo extends AbstractMigration {

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
            ->addColumn('active_job_id', 'integer', [
				'length' => 11,
				'null' => true,
				'default' => null
			])
			->addColumn('arguments', 'string', [
				'length' => 150,
				'null' => true,
				'default' => null,
			])
			->save();
	}

}
