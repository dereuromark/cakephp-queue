<?php
declare(strict_types=1);

namespace Queue\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;

/**
 * Array type for handling behaviors that need array passthrough.
 *
 * Useful for array handling behaviors as it avoids automatic conversion
 * that might interfere with custom array-handling logic.
 */
class ArrayType extends BaseType {

	/**
	 * @inheritDoc
	 */
	public function toDatabase(mixed $value, Driver $driver): mixed {
		if ($value === null || is_string($value)) {
			return $value;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function toPHP(mixed $value, Driver $driver): mixed {
		if ($value === null || is_array($value) || is_string($value)) {
			return $value;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function marshal(mixed $value): mixed {
		if (is_array($value) || is_string($value)) {
			return $value;
		}

		return null;
	}

}
