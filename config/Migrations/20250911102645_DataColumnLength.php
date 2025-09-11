<?php

use Cake\Error\Debugger;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\AdapterWrapper;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class DataColumnLength extends AbstractMigration {

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
		if ($this->getUnwrappedAdapter() instanceof MysqlAdapter) {
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

	/**
	 * Gets the unwrapped adapter
	 *
	 * @return \Phinx\Db\Adapter\AdapterInterface|null
	 */
	private function getUnwrappedAdapter(): ?AdapterInterface {
		$adapter = $this->adapter;

		while ($adapter instanceof AdapterWrapper) {
			$adapter = $adapter->getAdapter();
		}

		return $adapter;
	}

}
