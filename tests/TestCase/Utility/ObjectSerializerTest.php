<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Queue\Utility\ObjectSerializer;

class ObjectSerializerTest extends TestCase {

	/**
	 * @var \Queue\Utility\ObjectSerializer
	 */
	protected $serializer;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->serializer = new ObjectSerializer();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->serializer);
	}

	/**
	 * @return void
	 */
	public function testSerialize() {
		$data = [
			'key' => 'string \ foo-bar',
			'int' => 0,
		];
		$result = $this->serializer->serialize($data);
		$this->assertSame('a:2:{s:3:"key";s:16:"string \ foo-bar";s:3:"int";i:0;}', $result);

		$reversedResult = $this->serializer->deserialize($result);
		$this->assertSame($data, $reversedResult);
	}

}
