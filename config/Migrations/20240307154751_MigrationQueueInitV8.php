<?php
declare(strict_types=1);

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class MigrationQueueInitV8 extends AbstractMigration {

	/**
	 * Up Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
	 *
	 * @return void
	 */
	public function up(): void {
		// We expect all v7 migrations to be run before this migration (including 20231112807150_MigrationAddIndex)
		$version = '20240307154751';
		if (ConnectionManager::getConfig('default')['driver'] === 'Cake\Database\Driver\Sqlserver') {
			$this->execute('DELETE FROM queue_phinxlog WHERE [version] < \'' . $version . '\'');
		} else {
			$this->execute('DELETE FROM queue_phinxlog WHERE version < \'' . $version . '\'');
		}

		if ($this->hasTable('queued_jobs')) {
			return;
		}

		$this->table('queued_jobs')
			->addColumn('job_task', 'string', [
				'default' => null,
				'limit' => 90,
				'null' => false,
			])
			->addColumn('data', 'text', [
				'default' => null,
				'null' => true,
			])
			->addColumn('job_group', 'string', [
				'default' => null,
				'limit' => 190,
				'null' => true,
			])
			->addColumn('reference', 'string', [
				'default' => null,
				'limit' => 190,
				'null' => true,
			])
			->addColumn('created', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->addColumn('notbefore', 'datetime', [
				'default' => null,
				'null' => true,
			])
			->addColumn('fetched', 'datetime', [
				'default' => null,
				'null' => true,
			])
			->addColumn('completed', 'datetime', [
				'default' => null,
				'null' => true,
			])
			->addColumn('progress', 'float', [
				'default' => null,
				'null' => true,
				'signed' => false,
			])
			->addColumn('attempts', 'tinyinteger', [
				'default' => '0',
				'null' => true,
				'signed' => false,
			])
			->addColumn('failure_message', 'text', [
				'default' => null,
				'null' => true,
			])
			->addColumn('workerkey', 'string', [
				'default' => null,
				'limit' => 45,
				'null' => true,
			])
			->addColumn('status', 'string', [
				'default' => null,
				'limit' => 190,
				'null' => true,
			])
			->addColumn('priority', 'integer', [
				'default' => '5',
				'null' => false,
				'signed' => false,
			])
			->addIndex(
				[
					'completed',
				],
				[
					'name' => 'completed',
				],
			)
			->addIndex(
				[
					'job_task',
				],
				[
					'name' => 'job_task',
				],
			)
			->create();

		$this->table('queue_processes')
			->addColumn('pid', 'string', [
				'default' => null,
				'limit' => 40,
				'null' => false,
			])
			->addColumn('created', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->addColumn('modified', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->addColumn('terminate', 'boolean', [
				'default' => false,
				'null' => false,
			])
			->addColumn('server', 'string', [
				'default' => null,
				'limit' => 90,
				'null' => true,
			])
			->addColumn('workerkey', 'string', [
				'default' => null,
				'limit' => 45,
				'null' => false,
			])
			->addIndex(
				[
					'workerkey',
				],
				[
					'name' => 'workerkey',
					'unique' => true,
				],
			)
			->addIndex(
				[
					'pid',
					'server',
				],
				[
					'name' => 'pid',
					'unique' => true,
				],
			)
			->create();
	}

	/**
	 * Down Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
	 *
	 * @return void
	 */
	public function down(): void {
		$this->table('queue_processes')->drop()->save();
		$this->table('queued_jobs')->drop()->save();
	}

}
