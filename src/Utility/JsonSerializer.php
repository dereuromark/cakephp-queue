<?php
declare(strict_types=1);

namespace Queue\Utility;

class JsonSerializer implements SerializerInterface {

	/**
	 * @var int
	 */
	protected const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

	/**
	 * @var int
	 */
	protected const DEPTH = 512;

	/**
	 * Serializes data in the appropriate format.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options Options normalizers/encoders have access to
	 * @return string
	 */
	public function serialize(array $data, array $options = []): string {
		$flags = $options['flags'] ?? static::FLAGS;
		$depth = $options['depth'] ?? static::DEPTH;

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
		$flags = $options['flags'] ?? static::FLAGS;
		$depth = $options['depth'] ?? static::DEPTH;

		return json_decode($data, true, $depth, $flags) ?: [];
	}

}
