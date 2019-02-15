<?php

App::uses('QueueShell', 'Queue.Console/Command');
App::uses('CakeTestCase', 'TestSuite');

class QueueShellTest extends CakeTestCase {

	public $QueueShell;

/**
 * Fixtures to load
 *
 * @var array
 */
	public $fixtures = [
		'plugin.queue.queued_task'
	];

	public function setUp() {
		parent::setUp();

		$this->QueueShell = new TestQueueShell();
		$this->QueueShell->initialize();
		$this->QueueShell->loadTasks();

		Configure::write('Queue', [
			'sleeptime' => 2,
			'gcprob' => 10,
			'defaultworkertimeout' => 3,
			'defaultworkerretries' => 1,
			'workermaxruntime' => 5,
			'cleanuptimeout' => 10,
			'exitwhennothingtodo' => false,
			'pidfilepath' => TMP . 'queue' . DS,
			'log' => false,
		]);
	}

/**
 * QueueShellTest::testObject()
 *
 * @return void
 */
	public function testObject() {
		$this->assertTrue(is_object($this->QueueShell));
		$this->assertInstanceOf('QueueShell', $this->QueueShell);
	}

/**
 * QueueShellTest::testStats()
 *
 * @return void
 */
	public function testStats() {
		$result = $this->QueueShell->stats();
		//debug($this->QueueShell->out);
		$this->assertTrue(in_array('Total unfinished Jobs      : 0', $this->QueueShell->out));
	}

/**
 * QueueShellTest::testSettings()
 *
 * @return void
 */
	public function testSettings() {
		$result = $this->QueueShell->settings();
		$this->assertTrue(in_array('* cleanuptimeout: 10', $this->QueueShell->out));
	}

/**
 * QueueShellTest::testAddInexistent()
 *
 * @return void
 */
	public function testAddInexistent() {
		$this->QueueShell->args[] = 'Foo';
		$result = $this->QueueShell->add();
		$this->assertTrue(in_array('Error: Task not Found: Foo', $this->QueueShell->out));
	}

/**
 * QueueShellTest::testAdd()
 *
 * @return void
 */
	public function testAdd() {
		$this->QueueShell->args[] = 'Example';
		$result = $this->QueueShell->add();
		//debug($this->QueueShell->out);
		$this->assertEmpty($this->QueueShell->out);

		$result = $this->QueueShell->runworker();
		//debug($this->QueueShell->out);
		$this->assertTrue(in_array('Running Job of type "Example"', $this->QueueShell->out));
	}

	/**
	 * QueueShellTest::testRetry()
	 *
	 * @return void
	 */
	public function testRetry() {
		$this->QueueShell->args[] = 'RetryExample';
		$result = $this->QueueShell->add();
		$this->assertEmpty($this->QueueShell->out);

		$result = $this->QueueShell->runworker();
		//debug($this->QueueShell->out);
		$this->assertTrue(in_array('Job did not finish, requeued.', $this->QueueShell->out));
	}

	/**
	 * QueueShellTest::testCallbacks()
	 *
	 * @return void
	 */
	public function testCallbacks() {
		$this->QueueShell->args[] = 'Callbacks';
		$result = $this->QueueShell->add();

		$result = $this->QueueShell->runworker();
		//debug($this->QueueShell->out);
		$this->assertTrue($this->QueueShell->QueueCallbacks->before);
		$this->assertTrue($this->QueueShell->QueueCallbacks->after);
	}

	/**
	 * QueueShellTest::testCallbackFail()
	 *
	 * @return void
	 */
	public function testCallbackFail() {
		$this->QueueShell->args[] = 'CallbackFail';
		$this->QueueShell->args[] = 'fail';
		$result = $this->QueueShell->add();

		$result = $this->QueueShell->runworker();
		debug($this->QueueShell->out);
		$this->assertTrue($this->QueueShell->QueueCallbackFail->fail);
	}


}

class TestQueueShell extends QueueShell {

	public $out = [];

	public function out($message = null, $newlines = 1, $level = Shell::NORMAL) {
		$this->out[] = $message;
	}

	protected function _getTaskConf() {
		parent::_getTaskConf();
		foreach ($this->_taskConf as &$conf) {
			$conf['timeout'] = 5;
			$conf['retries'] = 1;
		}

		return $this->_taskConf;
	}

}
