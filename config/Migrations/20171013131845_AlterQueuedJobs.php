<?php

use Cake\Error\Debugger;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class AlterQueuedJobs extends AbstractMigration {

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
		$table = $this->table('queued_jobs');

		try {
			$adapter = new MysqlAdapter([]);
			if ($adapter->getSqlType('text', 'mediumtext')) {
				$table->changeColumn('failure_message', 'text', [
					'limit' => MysqlAdapter::TEXT_MEDIUM,
					'null' => true,
					'default' => null,
				]);
			}
		} catch (Exception $e) {
			Debugger::dump($e->getMessage());
		}
	}

}
