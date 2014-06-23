<?php
/**
 * PHP 5
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Send mail using Queue plugin
 *
 */
class QueueTransport extends AbstractTransport {

/**
 * Send mail
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 */
	public function send(CakeEmail $email) {
		if (!empty($this->_config['queue'])) {
			$this->_config = $this->_config['queue'] + $this->_config;
			$email->config((array)$this->_config['queue'] + array('queue' => array()));
			unset($this->_config['queue']);
		}

		$transport = $this->_config['transport'];
		$email->transport($transport);

		$QueuedTask = ClassRegistry::init('Queue.QueuedTask');
		$result = $QueuedTask->createJob('Email', array('transport' => $transport, 'settings' => $email));
		$result['headers'] = '';
		$result['message'] = '';
		return $result;
	}

}
