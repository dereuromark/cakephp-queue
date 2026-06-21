<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Queue\Model\Table\QueuedJobsTable as BaseQueuedJobsTable;

class QueuedJobsTable extends BaseQueuedJobsTable {

	/**
	 * Clear current worker key to generate new
	 *
	 * @return void
	 */
	public function clearKey(): void {
		$this->_key = null;
	}

}
