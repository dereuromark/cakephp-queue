<?php

use Cake\Datasource\ConnectionManager;
use Phinx\Migration\AbstractMigration;

class MigrationAddIndex extends AbstractMigration {

	/**
	 * @return void
	 */
	public function change() {
		// Shim: make sure this is void when a migrating with `202311128071500` instead of `20231112807150` has been run already.
		$version = '202311128071500';
		if (ConnectionManager::getConfig('default')['driver'] === 'Cake\Database\Driver\Sqlserver') {
			$result = $this->query('SELECT TOP(1) * FROM queue_phinxlog WHERE version = \'' . $version . '\'')->fetch();
			if ($result) {
				$this->execute('DELETE TOP(1) FROM queue_phinxlog WHERE version = \'' . $version . '\'');

				return;
			}
		} else {
			$result = $this->query('SELECT * FROM queue_phinxlog WHERE version = \'' . $version . '\' LIMIT 1')->fetch();
			if ($result) {
				$this->execute('DELETE FROM queue_phinxlog WHERE version = \'' . $version . '\' LIMIT 1');

				return;
			}
		}

		$table = $this->table('queued_jobs');
		$table
			->addIndex('completed')
			->addIndex('job_task')
			->update();
	}

}
