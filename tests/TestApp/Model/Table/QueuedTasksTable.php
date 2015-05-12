<?php

namespace TestApp\Model\Table;

use Queue\Model\Table\QueuedTasksTable as BaseQueuedTasksTable;

class QueuedTasksTable extends BaseQueuedTasksTable {

	public $cacheSources = false;

	/**
	 * Clear current worker key to generate new
	 *
	 * @return void
	 */
	public function clearKey() {
		$this->_key = null;
	}

}
