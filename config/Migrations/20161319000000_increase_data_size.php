<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;
use Cake\Error\Debugger;

class IncreaseDataSize extends AbstractMigration {

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
		$table = $this->table('queued_tasks');

		try {
			if(MysqlAdapter::getSqlType('text', 'longtext')) {
				$table->changeColumn('data', 'text', [
					'limit' => MysqlAdapter::TEXT_LONG,
					'null' => true,
					'default' => null,
				]);
			}
		} catch (Exception $e) {
			Debugger::dump($e->getMessages());
		} finally {
			return true;
		}
	}

}
