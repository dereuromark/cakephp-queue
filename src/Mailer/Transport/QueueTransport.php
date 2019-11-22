<?php
/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Queue\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\ORM\TableRegistry;

/**
 * Send mail using Queue plugin
 */
class QueueTransport extends AbstractTransport {

	/**
	 * Send mail
	 *
	 * @param \Cake\Mailer\Message $message
	 * @return array
	 */
	public function send(Message $message): array {
		if (!empty($this->_config['queue'])) {
			$this->_config = $this->_config['queue'] + $this->_config;
			$message->setConfig((array)$this->_config['queue'] + ['queue' => []]);
			unset($this->_config['queue']);
		}

		$transport = $this->_config['transport'];
		//$message->setTransport($transport);

		/** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
		$QueuedJobs = TableRegistry::getTableLocator()->get('Queue.QueuedJobs');
		$result = $QueuedJobs->createJob('Email', ['transport' => $transport, 'settings' => $message]);
		$result['headers'] = '';
		$result['message'] = '';

		return $result->toArray();
	}

}
