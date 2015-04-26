<?php

namespace TestApp\Model\Table;

use Queue\Model\Table\QueuedTasksTable as BaseQueuedTasksTable;

class QueuedTasksTable extends BaseQueuedTasksTable {

	public $cacheSources = false;

	public function clearKey() {
		$this->_key = null;
	}

}
