<?php

namespace Queue\Shell\Task;

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
	 * @return void
	 */
	public function add();

}
