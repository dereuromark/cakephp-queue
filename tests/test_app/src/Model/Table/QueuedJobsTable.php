<?php

namespace TestApp\Model\Table;

use Queue\Model\Table\QueuedJobsTable as BaseQueuedJobsTable;

class QueuedJobsTable extends BaseQueuedJobsTable {

	/**
	 * @var bool
	 */
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
