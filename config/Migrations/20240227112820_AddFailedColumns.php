<?php

use Phinx\Migration\AbstractMigration;

class AddFailedColumns extends AbstractMigration {

	/**
	 * @return void
	 */
	public function change() {
		$this->table('queued_jobs')
			->addColumn('failed', 'datetime', [
				'after' => 'fetched',
				'null' => true,
				'default' => null
			])
			->save();
	}

}
