<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Config;

use Cake\TestSuite\TestCase;
use Queue\Config\JobConfig;

class JobConfigTest extends TestCase {

	/**
	 * @return void
	 */
	public function testCreate() {
		$jobConfig = new JobConfig();
		$jobConfig->setPriority(1);
		$jobConfig->setGroup('mygroup');
		$jobConfig->setReferenceOrFail('reference');
		$jobConfig->setNotBefore('+1 hour');

		$array = $jobConfig->toArray();

		$expected = [
			'priority' => 1,
			'notbefore' => '+1 hour',
			'job_group' => 'mygroup',
			'reference' => 'reference',
			'status' => null,
		];
		$this->assertSame($expected, $array);
	}

}
