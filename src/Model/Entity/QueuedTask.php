<?php

namespace Queue\Model\Entity;

use Cake\ORM\Entity;

class QueuedTask extends Entity {

	/*
	protected function _getStatus() {
		// $this->virtualFields['status'] = '(CASE WHEN ' . 'notbefore > NOW() THEN \'NOT_READY\' WHEN ' . 'fetched IS null THEN \'NOT_STARTED\' WHEN ' . 'fetched IS NOT null AND ' . 'completed IS null AND ' . 'failed = 0 THEN \'IN_PROGRESS\' WHEN ' . 'fetched IS NOT null AND ' . 'completed IS null AND ' . 'failed > 0 THEN \'FAILED\' WHEN ' . 'fetched IS NOT null AND ' . 'completed IS NOT null THEN \'COMPLETED\' ELSE \'UNKNOWN\' END)';
	}
	*/

}
