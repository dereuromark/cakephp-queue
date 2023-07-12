<?php
declare(strict_types=1);

namespace Queue\Queue;

/**
 * Any task needs to at least implement add().
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
interface AddInterface {

	/**
	 * Allows adding a task to the queue.
	 *
	 * Will create one example job in the queue, which later will be executed using run().
	 *
	 * @param string|null $data Optional data for the task, make sure to "quote multi words"
	 *
	 * @return void
	 */
	public function add(?string $data): void;

}
