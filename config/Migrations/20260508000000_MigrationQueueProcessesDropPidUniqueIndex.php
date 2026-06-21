<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class MigrationQueueProcessesDropPidUniqueIndex extends BaseMigration {

	/**
	 * @return void
	 */
	public function up(): void {
		$this->table('queue_processes')
			->removeIndexByName('pid')
			->addIndex(['pid', 'server'], ['name' => 'pid_server'])
			->save();
	}

	/**
	 * @return void
	 */
	public function down(): void {
		$this->table('queue_processes')
			->removeIndexByName('pid_server')
			->addIndex(['pid', 'server'], ['name' => 'pid', 'unique' => true])
			->save();
	}

}
