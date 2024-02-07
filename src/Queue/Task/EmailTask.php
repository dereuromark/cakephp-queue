<?php
declare(strict_types=1);

namespace Queue\Queue\Task;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Mailer\TransportFactory;
use Psr\Log\LoggerInterface;
use Queue\Console\Io;
use Queue\Model\QueueException;
use Queue\Queue\AddFromBackendInterface;
use Queue\Queue\AddInterface;
use Queue\Queue\Task;
use Throwable;

/**
 * A convenience task ready to use for asynchronously sending basic emails.
 * Uses basic Message object.
 *
 * Especially useful is the fact that sending is auto-retried as per your config.
 * Will not drop the email if successfully sent, you can decide to even retry manually again afterwards.
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class EmailTask extends Task implements AddInterface, AddFromBackendInterface {

	public ?int $timeout = 60;

	public Mailer $mailer;

	public Message $message;

	/**
	 * List of default variables for Email class.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [];

	/**
	 * @param \Queue\Console\Io|null $io IO
	 * @param \Psr\Log\LoggerInterface|null $logger
	 */
	public function __construct(?Io $io = null, ?LoggerInterface $logger = null) {
		parent::__construct($io, $logger);

		$adminEmail = Configure::read('Config.adminEmail');
		if ($adminEmail) {
			$this->defaults['from'] = $adminEmail;
		}
	}

	/**
	 * "Add" the task, not possible for EmailTask without adminEmail configured.
	 *
	 * @param string|null $data
	 *
	 * @return void
	 */
	public function add(?string $data): void {
		$adminEmail = Configure::read('Config.adminEmail');
		if ($adminEmail) {
			$data = [
				'settings' => [
					'to' => $adminEmail,
					'subject' => 'Test Subject',
					'from' => $adminEmail,
				],
				'content' => 'Hello world',
			];
			$this->QueuedJobs->createJob('Queue.Email', $data);
			$this->io->success('OK, job created for email `' . $adminEmail . '`, now run the worker');

			return;
		}

		$this->io->err('Queue Email Task cannot be added via Console without `Config.adminEmail` being set.');
		$this->io->out('Please set this config value in your app.php Configure config. It will use this for to+from then.');
		$this->io->out('Or use createJob() on the QueuedTasks Table to create a proper QueueEmail job.');
		$this->io->out('The payload $data array should look something like this:');
		$this->io->out(var_export([
			'settings' => [
				'to' => 'email@example.com',
				'subject' => 'Email Subject',
				'from' => 'system@example.com',
				'template' => 'sometemplate',
			],
			'content' => 'hello world',
		], true));
		$this->io->out('Alternatively, you can pass the whole Mailer in `settings` key.');
	}

	/**
	 * @param \Cake\Mailer\Message $message
	 *
	 * @return array
	 */
	public static function serialize(Message $message): array {
		return $message->__serialize();
	}

	/**
	 * @param \Cake\Mailer\Message $object
	 * @param array $config
	 *
	 * @return \Cake\Mailer\Message
	 */
	public static function unserialize(Message $object, array $config): Message {
		return $object->createFromArray($config);
	}

	/**
	 * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
	 * @param int $jobId The id of the QueuedJob entity
	 *
	 * @throws \Queue\Model\QueueException
	 * @throws \Throwable
	 *
	 * @return void
	 */
	public function run(array $data, int $jobId): void {
		if (!isset($data['settings'])) {
			throw new QueueException('Queue Email task called without settings data.');
		}

		/** @var class-string<\Cake\Mailer\Message>|object|null $class */
		$class = $data['class'] ?? null;
		/** @var \Cake\Mailer\Message|null $object */
		$object = $class ? new $class() : null;
		if ($class && $object && (is_subclass_of($class, Message::class) || is_a($object, Message::class))) {
			$settings = $data['settings'];
			$serialized = $data['serialized'] ?? false;

			if ($serialized) {
				$this->message = is_array($settings) ? static::unserialize($object, $settings) : unserialize($settings);
			} else {
				/** @var class-string<\Cake\Mailer\Message> $class */
				$this->message = new $class($settings);
			}

			try {
				$transport = TransportFactory::get($data['transport'] ?? 'default');
				$result = $transport->send($this->message);
			} catch (Throwable $e) {
				$error = $e->getMessage();
				$error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL . $e->getTraceAsString();
				Log::write('error', $error);

				throw $e;
			}

			if (!$result) {
				throw new QueueException('Could not send email.');
			}

			return;
		}

		$this->mailer = $this->getMailer();

		$settings = $data['settings'] + $this->defaults;
		foreach ($settings as $method => $setting) {
			$setter = 'set' . ucfirst($method);
			if (in_array($method, ['theme', 'template', 'layout'], true)) {
				call_user_func_array([$this->mailer->viewBuilder(), $setter], (array)$setting);

				continue;
			}
			if (in_array($method, ['helper', 'helpers'], true)) {
				$setter = 'add' . ucfirst($method);
				call_user_func_array([$this->mailer->viewBuilder(), $setter], (array)$setting);

				continue;
			}

			call_user_func_array([$this->mailer, $setter], (array)$setting);
		}

		$this->mailer->setTransport($data['transport'] ?? 'default');

		$message = null;
		if (isset($data['content'])) {
			$message = $data['content'];
		}
		if (!empty($data['vars'])) {
			$this->mailer->setViewVars($data['vars']);
		}
		if (!empty($data['headers'])) {
			if (!is_array($data['headers'])) {
				throw new QueueException('Please provide headers as array.');
			}
			$this->mailer->getMessage()->setHeaders($data['headers']);
		}

		$this->mailer->deliver((string)$message);
	}

	/**
	 * Check if Mail class exists and create instance
	 *
	 * @throws \Queue\Model\QueueException
	 *
	 * @return \Cake\Mailer\Mailer
	 */
	protected function getMailer(): Mailer {
		/** @phpstan-var class-string<\Cake\Mailer\Mailer> $class */
		$class = Configure::read('Queue.mailerClass');
		if (!$class) {
			$class = 'Tools\Mailer\Mailer';
			if (!class_exists($class)) {
				$class = 'Cake\Mailer\Mailer';
			}
		}
		if (!class_exists($class)) {
			throw new QueueException(sprintf('Configured mailer class `%s` in `%s` not found.', $class, static::class));
		}

		return new $class();
	}

}
