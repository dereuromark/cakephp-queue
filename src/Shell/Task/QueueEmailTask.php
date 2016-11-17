<?php

namespace Queue\Shell\Task;

use Cake\Log\Log;
use Cake\Mailer\Email;
use Exception;

/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class QueueEmailTask extends QueueTask {

	/**
	 * List of default variables for EmailComponent
	 *
	 * @var array
	 */
	public $defaults = [
		'to' => null,
		'from' => null,
	];

	/**
	 * @var int
	 */
	public $timeout = 120;

	/**
	 * @var int
	 */
	public $retries = 1;

	/**
	 * @var \Cake\Mailer\Email
	 */
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
		$this->out(var_export([
			'settings' => [
				'to' => 'email@example.com',
				'subject' => 'Email Subject',
				'from' => 'system@example.com',
				'template' => 'sometemplate',
			],
			'vars' => [
				'content' => 'hello world',
			],
		], true));
		$this->out('Alternativly, you can pass the whole EmailLib to directly use it.');
	}

	/**
	 * @param mixed $data Job data
	 * @param int|null $id The id of the QueuedTask
	 * @return bool Success
	 */
	public function run(array $data, $id) {
		if (!isset($data['settings'])) {
			$this->err('Queue Email task called without settings data.');
			return false;
		}

		/* @var \Cake\Mailer\Email $email */
		$email = $data['settings'];
		if (is_object($email) && $email instanceof Email) {
			try {
				$transportClassNames = $email->configuredTransport();
				$result = $email->transport($transportClassNames[0])->send();

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
				Log::write('email_error', $error);

				return false;
			}
		}

		$class = 'Tools\Mailer\Email';
		if (!class_exists($class)) {
			$class = 'Cake\Mailer\Email';
		}
		$this->Email = new $class();

		$settings = array_merge($this->defaults, $data['settings']);
		foreach ($settings as $method => $setting) {
			call_user_func_array([$this->Email, $method], (array)$setting);
		}
		$message = null;
		if (!empty($data['vars'])) {
			if (isset($data['vars']['content'])) {
				$message = $data['vars']['content'];
			}
			$this->Email->viewVars($data['vars']);
		}

		return (bool)$this->Email->send($message);
	}

	/**
	 * Log message
	 *
	 * @param array $contents log-data
	 * @param mixed $log int for loglevel, array for merge with log-data
	 * @return void
	 */
	protected function _log($contents, $log) {
		$config = [
			'level' => LOG_DEBUG,
			'scope' => 'email',
		];
		if ($log !== true) {
			if (!is_array($log)) {
				$log = ['level' => $log];
			}
			$config = array_merge($config, $log);
		}
		/* for now
		Log::write(
			$config['level'],
			PHP_EOL . $contents['headers'] . PHP_EOL . $contents['message'],
			$config['scope']
		);
		*/
	}

}
