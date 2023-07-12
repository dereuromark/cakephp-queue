<?php
declare(strict_types=1);

namespace Foo\Bar\Test\TestCase\Shell\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Foo\Bar\Shell\Task\QueueFooPluginTask;
use Shim\TestSuite\ConsoleOutput;

class QueueFooPluginTaskTest extends TestCase {
    /**
     * @return void
     */
    public function testRun(): void {
        $this->out = new ConsoleOutput();
        $this->err = new ConsoleOutput();
        $io = new ConsoleIo($this->out, $this->err);

        $task = new QueueFooPluginTask($io);
    }
}
