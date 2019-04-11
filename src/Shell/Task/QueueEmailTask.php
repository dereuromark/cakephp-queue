<?php

namespace Queue\Shell\Task;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Exception;
use Queue\Model\QueueException;
use Throwable;

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
			'content' => 'hello world',
		], true));
		$this->out('Alternativly, you can pass the whole EmailLib to directly use it.');
	}

	/**
	 * @param array $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 * @return void
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function run(array $data, $jobId) {
		if (!isset($data['settings'])) {
			throw new QueueException('Queue Email task called without settings data.');
		}

		/** @var \Cake\Mailer\Email|null $email */
		$email = $data['settings'];
		if (is_object($email) && $email instanceof Email) {
			$this->Email = $email;

			$result = null;
			try {
				if (!empty($data['transport'])) {
					$email->setTransport($data['transport']);
				}
				$content = isset($data['content']) ? $data['content'] : null;
				$result = $email->send($content);

			} catch (Throwable $e) {
				$error = $e->getMessage();
				$error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL . $e->getTraceAsString();
				Log::write('error', $error);

				throw $e;

			} catch (Exception $e) {
				$error = $e->getMessage();
				$error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL . $e->getTraceAsString();
				Log::write('error', $error);

				throw $e;
			}

			if (!$result) {
				throw new QueueException('Could not send email.');
			}

			return;
		}

		$this->Email = $this->_getMailer();

		$settings = array_merge($this->defaults, $data['settings']);
		foreach ($settings as $method => $setting) {
			call_user_func_array([$this->Email, $method], (array)$setting);
		}
		$message = null;
		if (isset($data['content'])) {
			$message = $data['content'];
		}
		if (!empty($data['vars'])) {
			$this->Email->setViewVars($data['vars']);
		}
		if (!empty($data['headers'])) {
			if (!is_array($data['headers'])) {
				throw new QueueException('Please provide headers as array.');
			}
			$this->Email->setHeaders($data['headers']);
		}

		if (!$this->Email->send($message)) {
			throw new QueueException('Could not send email.');
		}
	}

	/**
	 * Check if Mail class exists and create instance
	 *
	 * @return \Cake\Mailer\Email
	 * @throws \Exception
	 */
	protected function _getMailer() {
		$class = Configure::read('Queue.mailerClass');
		if (!$class) {
			$class = 'Tools\Mailer\Email';
			if (!class_exists($class)) {
				$class = 'Cake\Mailer\Email';
			}
		}
		if (!class_exists($class)) {
			throw new QueueException(sprintf('Configured mailer class `%s` in `%s` not found.', $class, get_class($this)));
		}

		return new $class();
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

		Log::write(
			$config['level'],
			PHP_EOL . $contents['headers'] . PHP_EOL . $contents['message'],
			$config['scope']
		);
	}

}
