<?php
declare(strict_types=1);

namespace TestApp\Queue\Task;

use Queue\Queue\Task;

class FooTask extends Task {
    /**
     * Example run function.
     * This function is executed, when a worker is executing a task.
     * The return parameter will determine, if the task will be marked completed, or be requeued.
     *
     * @param array<string, mixed> $data The array passed to QueuedJobsTable::createJob()
     * @param int $jobId The id of the QueuedJob entity
     * @return void
     */
    public function run(array $data, int $jobId): void {
        $this->io->out('CakePHP Foo Example.');
    }
}
