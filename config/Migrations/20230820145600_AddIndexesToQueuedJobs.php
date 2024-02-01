<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddIndexesToQueuedJobs extends AbstractMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function change()
    {
		$indexes = [
		[
			'reference',
			'job_task',
		],[
			'reference',
		],[
			'job_group',
			'job_task',
			'notbefore',
		],[
			'completed',
			'attempts',
			'job_group',
			'job_task',
			'priority',
			'notbefore',
		],[
			'completed',
			'job_task',
			'priority',
			'notbefore',
		],[
			'job_group',
			'job_task',
			'notbefore',
		],[
			'job_task',
			'notbefore',
		]];

		$table = $this->table('queued_jobs');
		foreach ($indexes as $index) {
			if ($this->isMigratingUp()) {
				$table->addIndex($index);
				continue;
			}
			$table->removeIndex($index);
		}

		$this->isMigratingUp() && $table->update();
    }
}


