<?php
declare(strict_types=1);

namespace Queue\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Queue\Utility\JsonSerializer;

class JsonSerializerTest extends TestCase {

	/**
	 * @var \Queue\Utility\JsonSerializer
	 */
	protected $serializer;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->serializer = new JsonSerializer();
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
		$this->assertSame('{"key":"string \\\\ foo-bar","int":0}', $result);

		$reversedResult = $this->serializer->deserialize($result);
		$this->assertSame($data, $reversedResult);
	}

}
