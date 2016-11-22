<?php
/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Queue\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * Send mail using Queue plugin
 */
class QueueTransport extends AbstractTransport {

	/**
	 * Send mail
	 *
	 * @param \Cake\Mailer\Email $email Email
	 * @return array
	 */
	public function send(Email $email) {
		if (!empty($this->_config['queue'])) {
			$this->_config = $this->_config['queue'] + $this->_config;
			$email->config((array)$this->_config['queue'] + ['queue' => []]);
			unset($this->_config['queue']);
		}

		$transport = $this->_config['transport'];
		$email->transport($transport);

		/* @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
		$QueuedJobs = TableRegistry::get('Queue.QueuedJobs');
		$result = $QueuedJobs->createJob('Email', ['transport' => $transport, 'settings' => $email]);
		$result['headers'] = '';
		$result['message'] = '';
		return $result;
	}

}
