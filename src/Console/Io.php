<?php

namespace Queue\Console;

use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;

/**
 * Composition class as proxy towards ConsoleIO - basically a shell replacement for inside business logic.
 */
class Io {

	/**
	 * @var \Cake\Console\ConsoleIo
	 */
	protected $_io;

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 */
	public function __construct(ConsoleIo $io) {
		$this->_io = $io;
	}

	/**
	 * Output at the verbose level.
	 *
	 * @param string|array $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @return int|null The number of bytes returned from writing to stdout.
	 */
	public function verbose($message, $newlines = 1) {
		return $this->_io->verbose($message, $newlines);
	}

	/**
	 * Output at all levels.
	 *
	 * @param string|array $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @return int|null The number of bytes returned from writing to stdout.
	 */
	public function quiet($message, $newlines = 1) {
		return $this->_io->quiet($message, $newlines);
	}

	/**
	 * Outputs a single or multiple messages to stdout. If no parameters
	 * are passed outputs just a newline.
	 *
	 * ### Output levels
	 *
	 * There are 3 built-in output level. ConsoleIo::QUIET, ConsoleIo::NORMAL, ConsoleIo::VERBOSE.
	 * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
	 * present in most shells. Using ConsoleIo::QUIET for a message means it will always display.
	 * While using ConsoleIo::VERBOSE means it will only display when verbose output is toggled.
	 *
	 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::out
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @param int $level The message's output level, see above.
	 * @return int|null The number of bytes returned from writing to stdout.
	 */
	public function out($message = '', $newlines = 1, $level = ConsoleIo::NORMAL) {
		return $this->_io->out($message, $newlines, $level);
	}

	/**
	 * Outputs a single or multiple error messages to stderr. If no parameters
	 * are passed outputs just a newline.
	 *
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @return int|null The number of bytes returned from writing to stderr.
	 */
	public function err($message = '', $newlines = 1) {
		$messages = (array)$message;
		foreach ($messages as $key => $message) {
			$messages[$key] = '<error>' . $message . '</error>';
		}

		return $this->_io->err($messages, $newlines);
	}

	/**
	 * Convenience method for out() that wraps message between <info /> tag
	 *
	 * @see http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::out
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @param int $level The message's output level, see above.
	 * @return int|null The number of bytes returned from writing to stdout.
	 */
	public function info($message = '', $newlines = 1, $level = ConsoleIo::NORMAL) {
		$messages = (array)$message;
		foreach ($messages as $key => $message) {
			$messages[$key] = '<info>' . $message . '</info>';
		}

		return $this->out($messages, $newlines, $level);
	}

	/**
	 * Convenience method for out() that wraps message between <comment /> tag
	 *
	 * @see http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::out
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @param int $level The message's output level, see above.
	 * @return int|null The number of bytes returned from writing to stdout.
	 */
	public function comment($message = '', $newlines = 1, $level = ConsoleIo::NORMAL) {
		$messages = (array)$message;
		foreach ($messages as $key => $message) {
			$messages[$key] = '<comment>' . $message . '</comment>';
		}

		return $this->out($messages, $newlines, $level);
	}

	/**
	 * Convenience method for err() that wraps message between <warning /> tag
	 *
	 * @see http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::err
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @return int|null The number of bytes returned from writing to stderr.
	 */
	public function warn($message = '', $newlines = 1) {
		$messages = (array)$message;
		foreach ($messages as $key => $message) {
			$messages[$key] = '<warning>' . $message . '</warning>';
		}

		return $this->_io->err($messages, $newlines);
	}

	/**
	 * Convenience method for out() that wraps message between <success /> tag
	 *
	 * @see http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::out
	 * @param string|string[] $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @param int $level The message's output level, see above.
	 * @return int|null The number of bytes returned from writing to stdout.
	 */
	public function success($message = '', $newlines = 1, $level = ConsoleIo::NORMAL) {
		$messages = (array)$message;
		foreach ($messages as $key => $message) {
			$messages[$key] = '<success>' . $message . '</success>';
		}

		return $this->out($messages, $newlines, $level);
	}

	/**
	 * Returns a single or multiple linefeeds sequences.
	 *
	 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::nl
	 * @param int $multiplier Number of times the linefeed sequence should be repeated
	 * @return string
	 */
	public function nl($multiplier = 1) {
		return $this->_io->nl($multiplier);
	}

	/**
	 * Outputs a series of minus characters to the standard output, acts as a visual separator.
	 *
	 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::hr
	 * @param int $newlines Number of newlines to pre- and append
	 * @param int $width Width of the line, defaults to 63
	 * @return void
	 */
	public function hr($newlines = 0, $width = 63) {
		$this->_io->hr($newlines, $width);
	}

	/**
	 * Displays a formatted error message
	 * and exits the application with status code 1
	 *
	 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#styling-output
	 * @param string $message The error message
	 * @param int $exitCode The exit code for the shell task.
	 * @throws \Cake\Console\Exception\StopException
	 * @return void
	 */
	public function abort($message, $exitCode = ConsoleIo::CODE_ERROR) {
		$this->_io->err('<error>' . $message . '</error>');

		throw new StopException($message, $exitCode);
	}

}
