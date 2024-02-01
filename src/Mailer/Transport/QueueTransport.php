<?php
declare(strict_types=1);

/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Queue\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Send mail using Queue plugin and Message objects.
 *
 * @method \Cake\ORM\Locator\TableLocator getTableLocator()
 */
class QueueTransport extends AbstractTransport {

	use LocatorAwareTrait;

	/**
	 * Send mail
	 *
	 * @param \Cake\Mailer\Message $message
	 *
	 * @return array<string, mixed>
	 */
	public function send(Message $message): array {
		if (!empty($this->_config['queue'])) {
			$this->_config = $this->_config['queue'] + $this->_config;
			$message->setConfig((array)$this->_config['queue'] + ['queue' => []]);
			unset($this->_config['queue']);
		}

		$transport = $this->_config['transport'] ?? null;

		/** @var \Queue\Model\Table\QueuedJobsTable $QueuedJobs */
		$QueuedJobs = $this->getTableLocator()->get('Queue.QueuedJobs');
		$result = $QueuedJobs->createJob('Queue.Email', ['transport' => $transport, 'settings' => $message]);
		$result['headers'] = $message->getHeadersString();
		$result['message'] = $message->getBodyString();

		return $result->toArray();
	}

}
