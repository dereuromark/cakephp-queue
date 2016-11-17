<?php
namespace TestApp\Shell;

use Cake\Console\Shell;
use Queue\Shell\QueueShell;

class TestQueueShell extends QueueShell {

	/**
	 * @var array
	 */
	public $out = [];

	/**
	 * Output function for Test
	 *
	 * @param string|null $message Message.
	 * @param int $newlines Newline.
	 * @param int $level Output level.
	 *
	 * @return void
	 */
	public function out($message = null, $newlines = 1, $level = Shell::NORMAL) {
		$this->out[] = $message;
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
