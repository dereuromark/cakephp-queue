<?php
declare(strict_types=1);

namespace Queue\Queue\Task;

use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\MailerAwareTrait;
use Queue\Model\QueueException;
use Queue\Queue\Task;
use Throwable;

/**
 * A convenience task ready to use for asynchronously sending reusable emails via Mailer classes.
 *
 * Especially useful is the fact that sending is auto-retried as per your config.
 * Will not drop the email if successfully sent, you can decide to even retry manually again afterwards.
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class MailerTask extends Task {

	use MailerAwareTrait;

	public ?int $timeout = 60;

	/**
	 * @var \Cake\Mailer\Mailer
	 */
	protected Mailer $mailer;

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 *
	 * @throws \Queue\Model\QueueException
	 * @throws \Cake\Mailer\Exception\MissingMailerException
	 * @throws \Throwable
	 *
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		if (!isset($data['class'])) {
			throw new QueueException('Queue Mailer task called without valid `mailer` class.');
		}
		if (!isset($data['action'])) {
			throw new QueueException('Queue Mailer task called without `action` data.');
		}

		$this->mailer = $this->getMailer($data['class']);

		try {
			$this->mailer->setTransport($data['transport'] ?? 'default');
			$result = $this->mailer->send($data['action'], $data['vars'] ?? []);
		} catch (Throwable $e) {
			$error = $e->getMessage();
			$error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL . $e->getTraceAsString();
			Log::write('error', $error);

			throw $e;
		}

		if (!$result) {
			throw new QueueException('Could not send email.');
		}
	}

}
