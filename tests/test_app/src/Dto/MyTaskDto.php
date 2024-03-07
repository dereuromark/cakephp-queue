<?php
declare(strict_types=1);

namespace TestApp\Dto;

use CakeDto\Dto\FromArrayToArrayInterface;

class MyTaskDto implements FromArrayToArrayInterface {

	protected array $data;

	/**
	 * @param array $data
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	/**
	 * @return string
	 */
	public function getFoo(): string {
		return 'foo';
	}

	/**
	 * @param array $array
	 *
	 * @return static
	 */
	public static function createFromArray(array $array): static {
		return new static($array);
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return $this->data;
	}

}
