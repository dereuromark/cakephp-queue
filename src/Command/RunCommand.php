<?php

namespace Queue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Queue\Console\Io;

/**
 * Main execution of queued jobs.
 */
class RunCommand extends Command {

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
		$parser->setDescription(
			'Runs a queue worker.'
		);

		return $parser;
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @return \Psr\Log\LoggerInterface
	 */
	protected function getLogger(Arguments $args): LoggerInterface {
		$logger = null;
		if (!empty($args->getOption('verbose'))) {
			$logger = Log::engine((string)$args->getOption('logger'));
		}

		return $logger ?? new NullLogger();
	}

	/**
	 * @param \Cake\Console\Arguments $args Arguments
	 * @param \Cake\Console\ConsoleIo $io ConsoleIo
	 * @return int|null|void
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		$logger = $this->getLogger($args);
		$io = new Io($io);
		//FIXME
		$processor = new Processor($io, $logger);

		return $processor->run();
	}

}
