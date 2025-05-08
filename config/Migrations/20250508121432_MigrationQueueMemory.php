<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MigrationQueueMemory extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
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
