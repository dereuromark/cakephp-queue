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

	/**
	 * Headers the Message builds itself; copying them over would pin a stale boundary or date.
	 *
	 * @var array<string>
	 */
	protected const GENERATED_HEADERS = ['Date', 'Message-ID', 'MIME-Version', 'Content-Type', 'Content-Transfer-Encoding'];

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
			'headerCharset' => [$message->getHeaderCharset()],
			'emailFormat' => [$message->getEmailFormat()],
			'subject' => [$message->getOriginalSubject()],
		];

		foreach ($settings as $setting => $value) {
			if ($value[0] === []) {
				unset($settings[$setting]);
			}
		}

		// Passed as-is, not as setter argument lists: EmailTask hands these to the Message directly.
		$headers = array_diff_key($message->getHeaders(), array_flip(static::GENERATED_HEADERS));
		if ($headers) {
			$settings['headers'] = $headers;
		}
		if ($message->getAttachments()) {
			$settings['attachments'] = $message->getAttachments();
		}
		$html = $message->getBodyHtml();
		if ($html !== '') {
			$settings['htmlMessage'] = $html;
		}
		$text = $message->getBodyText();
		if ($text !== '') {
			$settings['textMessage'] = $text;
		}

		$QueuedJobs = $this->getQueuedJobsModel();
		$result = $QueuedJobs->createJob('Queue.Email', [
			'settings' => $settings,
			'transport' => $this->_config['transport'] ?? null,
		]);
		$result->headers = $message->getHeadersString();
		$result->message = $message->getBodyString();

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
