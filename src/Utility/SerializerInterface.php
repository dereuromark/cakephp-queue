<?php
declare(strict_types=1);

namespace Queue\Utility;

interface SerializerInterface {

	/**
	 * Serializes data in the appropriate format.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options Options normalizers/encoders have access to
	 *
	 * @return string
	 */
	public function serialize(array $data, array $options = []): string;

	/**
	 * Deserializes data into the given type.
	 *
	 * @param string $data
	 * @param array<string, mixed> $options
	 *
	 * @return array<string, mixed>
	 */
	public function deserialize(string $data, array $options = []): array;

}
