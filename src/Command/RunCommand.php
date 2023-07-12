<?php
declare(strict_types=1);

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\ContainerInterface;
use Cake\Log\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Queue\Console\Io;
use Queue\Queue\Processor;

/**
 * Main execution of queued jobs.
 */
class RunCommand extends Command {

	/**
	 * @var \Cake\Core\ContainerInterface
	 */
	protected ContainerInterface $container;

	/**
	 * @param \Cake\Core\ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	/**
	 * @inheritDoc
	 */
	public static function defaultName(): string {
		return 'queue run';
	}

	/**
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function getOptionParser(): ConsoleOptionParser {
		$parser = parent::getOptionParser();

		$parser->addOption('config', [
			'default' => 'default',
			'help' => 'Name of a queue config to use',
			'short' => 'c',
		]);
		$parser->addOption('logger', [
			'help' => 'Name of a configured logger',
			'default' => 'stdout',
			'short' => 'l',
		]);
		$parser->addOption('max-runtime', [
			'help' => 'Seconds for max runtime',
			'default' => null,
			'short' => 'r',
		]);

		$parser->addOption('group', [
			'short' => 'g',
			'help' => 'Group (comma separated list possible)',
			'default' => null,
		]);
		$parser->addOption('type', [
			'short' => 't',
			'help' => 'Type (comma separated list possible)',
			'default' => null,
		]);

		$parser->setDescription(
			'Simple and minimalistic job queue (or deferred-task) system.'
			. PHP_EOL
			. 'This command runs a queue worker.',
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 *
	 * @return \Psr\Log\LoggerInterface
	 */
	protected function getLogger(Arguments $args): LoggerInterface {
		$logger = null;
		if (!$args->getOption('quiet')) {
			$logger = Log::engine((string)$args->getOption('logger'));
		}

		return $logger ?? new NullLogger();
	}

	/**
	 * Run a QueueWorker loop.
	 * Runs a Queue Worker process which will try to find unassigned jobs in the queue
	 * which it may run and try to fetch and execute them.
	 *
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 *
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$logger = $this->getLogger($args);
		$io = new Io($io);
		$processor = new Processor($io, $logger, $this->container);

		return $processor->run($args->getOptions());
	}

}
