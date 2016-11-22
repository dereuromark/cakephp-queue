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
			$email->config((array)$this->_config['queue'] + ['queue' => []]);
			unset($this->_config['queue']);
		}

		$settings = [
			'from' => [$email->from()],
			'to' => [$email->to()],
			'cc' => [$email->cc()],
			'bcc' => [$email->bcc()],
			'charset' => [$email->charset()],
			'replyTo' => [$email->replyTo()],
			'readReceipt' => [$email->readReceipt()],
			'returnPath' => [$email->returnPath()],
			'messageId' => [$email->messageId()],
			'domain' => [$email->domain()],
			'getHeaders' => [$email->getHeaders()],
			'headerCharset' => [$email->headerCharset()],
			'theme' => [$email->theme()],
			'profile' => [$email->profile()],
			'emailFormat' => [$email->emailFormat()],
			'subject' => method_exists($email, 'getOriginalSubject') ? [$email->getOriginalSubject()] : [$email->subject()],
			'transport' => [$this->_config['transport']],
			'attachments' => [$email->attachments()],
			'template' => $email->template(), //template() gives 2 values - template and layout
			'viewVars' => [$email->viewVars()]
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
		return $result;
	}

	/**
	 * @return \Queue\Model\Table\QueuedJobsTable
	 */
	protected function getQueuedJobsModel() {
		return TableRegistry::get('Queue.QueuedJobs');
	}

}
