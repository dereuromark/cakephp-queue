<?php
/**
 * PHP 5
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Queue\Network\Email;

use Cake\Network\Email\AbstractTransport;
use Cake\Network\Email\Email;
use Cake\ORM\TableRegistry;

/**
 * Send mail using Queue plugin
 *
 */
class QueueTransport extends AbstractTransport {

	/**
	 * Send mail
	 *
	 * @param Email $email Email
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

		$QueuedTasks = TableRegistry::get('Queue.QueuedTasks');
		$result = $QueuedTasks->createJob('Email', ['transport' => $transport, 'settings' => $email]);
		$result['headers'] = '';
		$result['message'] = '';
		return $result;
	}

}
