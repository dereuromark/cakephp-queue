<?php

namespace Queue\Utility;

class ObjectSerializer implements SerializerInterface {

	/**
	 * Serializes data in the appropriate format.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options Options normalizers/encoders have access to
	 * @return string
	 */
	public function serialize(array $data, array $options = []): string {
		return serialize($data);
	}

	/**
	 * Deserializes data into the given type.
	 *
	 * @param string $data
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function deserialize(string $data, array $options = []): array {
		return unserialize($data, $options) ?: [];
	}

}
