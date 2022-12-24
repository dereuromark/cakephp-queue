<?php

namespace Queue\Utility;

class JsonSerializer implements SerializerInterface {

	/**
	 * Serializes data in the appropriate format.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options Options normalizers/encoders have access to
	 * @return string
	 */
	public function serialize(array $data, array $options = []): string {
		$flags = $options['flags'] ?? 0;
		$depth = $options['depth'] ?? 512;

		return (string)json_encode($data, $flags, $depth);
	}

	/**
	 * Deserializes data into the given type.
	 *
	 * @param string $data
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function deserialize(string $data, array $options = []): array {
		$flags = $options['flags'] ?? 0;
		$depth = $options['depth'] ?? 512;

		return json_decode($data, true, $depth, $flags) ?: [];
	}

}
