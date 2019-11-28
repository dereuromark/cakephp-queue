<?php

namespace App\Shell;

use Queue\Shell\QueueShell;

class TestQueueShell extends QueueShell {

	/**
	 * @var string[]
	 */
	public $out = [];

	/**
	 * Output function for Test
	 *
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Newline.
	 * @param int $level Output level.
	 *
	 * @return int|null
	 */
	public function out($message, int $newlines = 1, int $level = self::NORMAL): ?int {
		$this->out[] = $message;

		return null;
	}

	/**
	 * Get task configuration
	 *
	 * @return array
	 */
	protected function _getTaskConf() {
		parent::_getTaskConf();
		foreach ($this->_taskConf as &$conf) {
			$conf['timeout'] = 5;
			$conf['retries'] = 1;
		}

		return $this->_taskConf;
	}

}
