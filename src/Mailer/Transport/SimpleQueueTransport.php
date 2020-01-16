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
class SimpleQueueTransport extends AbstractTransport {

	/**
	 * Send mail
	 *
	 * @param \Cake\Mailer\Email $email Email
	 * @return array
	 */
	public function send(Email $email) {
		if (!empty($this->_config['queue'])) {
			$this->_config = $this->_config['queue'] + $this->_config;
			$email->setConfig((array)$this->_config['queue'] + ['queue' => []]);
			unset($this->_config['queue']);
		}

		$settings = [
			'from' => [$email->getFrom()],
			'to' => [$email->getTo()],
			'cc' => [$email->getCc()],
			'bcc' => [$email->getBcc()],
			'charset' => [$email->getCharset()],
			'replyTo' => [$email->getReplyTo()],
			'readReceipt' => [$email->getReadReceipt()],
			'returnPath' => [$email->getReturnPath()],
			'messageId' => [$email->getMessageId()],
			'domain' => [$email->getDomain()],
			'headers' => [$email->getHeaders()],
			'headerCharset' => [$email->getHeaderCharset()],
			'profile' => [$email->getProfile()],
			'emailFormat' => [$email->getEmailFormat()],
			'subject' => method_exists($email, 'getOriginalSubject') ? [$email->getOriginalSubject()] : [$email->getSubject()],
			'transport' => [$this->_config['transport']],
			'attachments' => [$email->getAttachments()],
			'theme' => [$email->viewBuilder()->getTheme()],
			'template' => [$email->viewBuilder()->getTemplate()],
			'layout' => [$email->viewBuilder()->getLayout()],
			'viewVars' => [$email->getViewVars()],
		];

		foreach ($settings as $setting => $value) {
			if (array_key_exists(0, $value) && ($value[0] === null || $value[0] === [])) {
				unset($settings[$setting]);
			}
		}

		$QueuedJobs = $this->getQueuedJobsModel();
		$result = $QueuedJobs->createJob('Email', ['settings' => $settings]);
		$result['headers'] = '';
		$result['message'] = '';

		return $result->toArray();
	}

	/**
	 * @return \Queue\Model\Table\QueuedJobsTable
	 */
	protected function getQueuedJobsModel() {
		/** @var \Queue\Model\Table\QueuedJobsTable $table */
		$table = TableRegistry::get('Queue.QueuedJobs');

		return $table;
	}

}
