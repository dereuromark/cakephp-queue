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
		$table->changeColumn('pid', 'string', [
			'length' => 40,
			'null' => false,
			'default' => null,
			'encoding' => 'ascii',
			'collation' => 'ascii_general_ci',
		]);
		$table->update();

		$table = $this->table('queued_jobs');
		$table->changeColumn('job_type', 'string', [
			'length' => 45,
			'null' => false,
			'default' => null,
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
		]);
		$table->changeColumn('job_group', 'string', [
			'length' => 255,
			'null' => true,
			'default' => null,
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
		]);
		$table->changeColumn('reference', 'string', [
			'length' => 255,
			'null' => true,
			'default' => null,
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
		]);
		$table->changeColumn('workerkey', 'string', [
			'length' => 45,
			'null' => true,
			'default' => null,
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
		]);
		$table->changeColumn('status', 'string', [
			'length' => 255,
			'null' => true,
			'default' => null,
			'encoding' => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
		]);
		$table->update();

		//TODO: check adapter and skip for postgres, instead of try/catch
		if ($this->adapter instanceof \Phinx\Db\Adapter\MysqlAdapter) {
			try {
				$table = $this->table('queued_jobs');
				$table->changeColumn('data', 'text', [
					'limit' => MysqlAdapter::TEXT_MEDIUM,
					'null' => true,
					'default' => null,
					'encoding' => 'utf8mb4',
					'collation' => 'utf8mb4_unicode_ci',
				]);
				$table->changeColumn('failure_message', 'text', [
					'limit' => MysqlAdapter::TEXT_MEDIUM,
					'null' => true,
					'default' => null,
					'encoding' => 'utf8mb4',
					'collation' => 'utf8mb4_unicode_ci',
				]);
				$table->update();
			} catch (Exception $e) {
				Debugger::dump($e->getMessage());
			}
		}
	}

}
