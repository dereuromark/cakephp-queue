<?php
declare(strict_types=1);

namespace Queue\Utility;

use Cake\Core\Configure;

class Serializer {

	/**
	 * Serializes data in the appropriate format.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options Options normalizers/encoders have access to
	 * @return string
	 */
	public static function serialize(array $data, array $options = []): string {
		$options = Configure::read('Queue.serializerOptions') ?: [];

		return static::getSerializer()->serialize($data, $options);
	}

	/**
	 * Deserializes data into the given type.
	 *
	 * @param string $data
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public static function deserialize(string $data, array $options = []): array {
		$options = Configure::read('Queue.serializerOptions') ?: [];

		return static::getSerializer()->deserialize($data, $options);
	}

	/**
	 * @return \Queue\Utility\SerializerInterface
	 */
	protected static function getSerializer(): SerializerInterface {
		/** @var class-string<\Queue\Utility\SerializerInterface> $class */
		$class = Configure::read('Queue.serializerClass') ?: ObjectSerializer::class;

		return new $class();
	}

}
