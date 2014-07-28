<?php
/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::uses('EmailLib', 'Tools.Lib');
App::uses('AppShell', 'Console/Command');

class QueueEmailTask extends AppShell {

/**
 * List of default variables for EmailComponent
 *
 * @var array
 */
	public $defaults = array(
		'to' => null,
		'from' => null,
	);

	public $timeout = 120;

	public $retries = 1;

/**
 * @var bool
 */
	public $autoUnserialize = true;

	public $Email;

/**
 * "Add" the task, not possible for QueueEmailTask
 *
 * @return void
 */
	public function add() {
		$this->err('Queue Email Task cannot be added via Console.');
		$this->out('Please use createJob() on the QueuedTask Model to create a Proper Email Task.');
		$this->out('The Data Array should look something like this:');
		$this->out(var_export(array(
			'settings' => array(
				'to' => 'email@example.com',
				'subject' => 'Email Subject',
				'from' => 'system@example.com',
				'template' => 'sometemplate'
			),
			'vars' => array(
				'content' => 'hello world',
			)
		), true));
		$this->out('Alternativly, you can pass the whole EmailLib to directly use it.');
	}

/**
 * QueueEmailTask::run()
 *
 * @param mixed $data Job data
 * @return bool Success
 */
	public function run($data) {
		if (!isset($data['settings'])) {
			$this->err('Queue Email task called without settings data.');
			return false;
		}

		if (is_object($email = $data['settings']) && $email instanceof CakeEmail) {
			try {
				$transport = $email->transportClass();
				$config = $email->config();
				$transport->config($config);
				$result = $transport->send($email);

				if (!isset($config['log']) || !empty($config['logTrace']) && $config['logTrace'] === true) {
					$config['log'] = 'email_trace';
				} elseif (!empty($config['logTrace'])) {
					$config['log'] = $config['logTrace'];
				}
				if (isset($config['logTrace']) && !$config['logTrace']) {
					$config['log'] = false;
				}

				if (!empty($config['logTrace'])) {
					$this->_log($result, $config['log']);
				}
				return (bool)$result;
			} catch (Exception $e) {

				$error = $e->getMessage();
				$error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL . $e->getTraceAsString();
				CakeLog::write('email_error', $error);

				return false;
			}
		}

		$this->Email = new EmailLib();
		$settings = array_merge($this->defaults, $data['settings']);
		foreach ($settings as $method => $setting) {
			call_user_func_array(array($this->Email, $method), (array)$setting);
		}
		$message = null;
		if (!empty($data['vars'])) {
			if (isset($data['vars']['content'])) {
				$message = $data['vars']['content'];
			}
			$this->Email->viewVars($data['vars']);
		}
		return $this->Email->send($message);
	}

/**
 * Log message
 *
 * @param array $contents log-data
 * @param mixed $log int for loglevel, array for merge with log-data
 * @return void
 */
	protected function _log($contents, $log) {
		$config = array(
			'level' => LOG_DEBUG,
			'scope' => 'email'
		);
		if ($log !== true) {
			if (!is_array($log)) {
				$log = array('level' => $log);
			}
			$config = array_merge($config, $log);
		}
		CakeLog::write(
			$config['level'],
			PHP_EOL . $contents['headers'] . PHP_EOL . $contents['message'],
			$config['scope']
		);
	}

}
