<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MigrationQueueMemory extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('queued_jobs')
			->addColumn('memory', 'integer', [
				'default' => null,
				'null' => true,
				'comment' => 'MB',
				'signed' => false,
			])
			->update();
	}

}
