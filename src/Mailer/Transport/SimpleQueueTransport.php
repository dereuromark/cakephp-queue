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
use Queue\Model\Table\QueuedJobsTable;

/**
 * Send mail using Queue plugin and Message settings.
 * This is only recommended for non-templated emails.
 *
 * @method \Cake\ORM\Locator\TableLocator getTableLocator()
 */
class SimpleQueueTransport extends AbstractTransport {

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

		$settings = [
			'from' => [$message->getFrom()],
			'to' => [$message->getTo()],
			'cc' => [$message->getCc()],
			'bcc' => [$message->getBcc()],
			'charset' => [$message->getCharset()],
			'replyTo' => [$message->getReplyTo()],
			'readReceipt' => [$message->getReadReceipt()],
			'returnPath' => [$message->getReturnPath()],
			'messageId' => [$message->getMessageId()],
			'domain' => [$message->getDomain()],
			'headers' => [$message->getHeaders()],
			'headerCharset' => [$message->getHeaderCharset()],
			'emailFormat' => [$message->getEmailFormat()],
			'subject' => [$message->getOriginalSubject()],
			'transport' => [$this->_config['transport']],
			'attachments' => [$message->getAttachments()],
		];

		foreach ($settings as $setting => $value) {
			if (array_key_exists(0, $value) && ($value[0] === null || $value[0] === [])) {
				unset($settings[$setting]);
			}
		}

		$QueuedJobs = $this->getQueuedJobsModel();
		$result = $QueuedJobs->createJob('Queue.Email', ['settings' => $settings]);
		$result['headers'] = $message->getHeadersString();
		$result['message'] = $message->getBodyString();

		return $result->toArray();
	}

	/**
	 * @return \Queue\Model\Table\QueuedJobsTable
	 */
	protected function getQueuedJobsModel(): QueuedJobsTable {
		/** @var \Queue\Model\Table\QueuedJobsTable $table */
		$table = $this->getTableLocator()->get('Queue.QueuedJobs');

		return $table;
	}

}
