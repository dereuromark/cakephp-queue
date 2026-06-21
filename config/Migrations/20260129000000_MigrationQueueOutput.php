<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MigrationQueueOutput extends BaseMigration {

	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$this->table('queued_jobs')
			->addColumn('output', 'text', [
				'default' => null,
				'null' => true,
			])
			->update();
	}

}
