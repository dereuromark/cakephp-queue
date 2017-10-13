<?php

use Cake\Error\Debugger;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class Utf8mb4Fix extends AbstractMigration {

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
		$table->changeColumn('pid', 'text', [
			'encoding' => 'ascii',
			'collation' => 'ascii_bin',
		]);

		$table = $this->table('queued_jobs');
		$table->changeColumn('job_type', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
		$table->changeColumn('data', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
		$table->changeColumn('job_group', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
		$table->changeColumn('reference', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
		$table->changeColumn('failure_message', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
		$table->changeColumn('workerkey', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
		$table->changeColumn('status', 'text', [
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_bin',
		]);
	}

}
