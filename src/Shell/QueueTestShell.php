<?php

namespace Queue\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Tools\Mailer\Email;

if (!defined('FORMAT_DB_DATE')) {
	define('FORMAT_DB_DATETIME', 'Y-m-d H:i:s');
}

/**
 * Testing email etc
 *
 * @author Mark Scherer
 */
class QueueTestShell extends Shell {

	/**
	 * @var string
	 */
	public $modelClass = 'Queue.QueuedTask';

	/**
	 * Test queue of email job
	 *
	 * @return void
	 */
	public function email() {
		$data = [
			'settings' => [
				'subject' => 'Some test - ' . date(FORMAT_DB_DATETIME),
				'to' => Configure::read('Config.admin_email'),
				'domain' => 'example.org',
			],
			'vars' => [
				'content' => 'I am a test',
			],
		];

		if ($this->QueuedTasks->createJob('Email', $data)) {
			$this->out('OK, test email created');
		} else {
			$this->err('Could not create test email');
		}
	}

	/**
	 * Test sending emails via CLI and Queue transport.
	 *
	 * @return void
	 */
	public function complete_email() {
		Configure::write('debug', 0);
		$Email = new Email();
		$Email->to('euromark@web.de', 'Mark Test');
		$Email->subject('Testing Message');
		$host = Configure::read('App.host');
		if ($host) {
			$Email->domain($host);
		}

		$config = $Email->config();
		if (!isset($config['queue'])) {
			return $this->error('queue key in config missing');
		}

		$res = $Email->send('Foo');
		if (!$res) {
			return $this->error('Could not send email: ' . $Email->getError());
		}
		$this->out('YEAH!');
	}

}
