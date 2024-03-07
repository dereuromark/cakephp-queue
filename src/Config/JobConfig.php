<?php

namespace Queue\Config;

use InvalidArgumentException;
use RuntimeException;

/**
 * JobConfig DTO
 *
 * For legacy reasons
 * - camelCased by default for input (application)
 * - under_scored by default for output (DB)
 */
class JobConfig {

	/**
	 * @var string
	 */
	public const FIELD_PRIORITY = 'priority';

	/**
	 * @var string
	 */
	public const FIELD_NOT_BEFORE = 'notBefore';

	/**
	 * @var string
	 */
	public const FIELD_JOB_GROUP = 'group';

	/**
	 * @var string
	 */
	public const FIELD_REFERENCE = 'reference';

	/**
	 * @var string
	 */
	public const FIELD_STATUS = 'status';

	/**
	 * For camelBacked input/output.
	 *
	 * E.g. `myFieldName`
	 *
	 * @var string
	 */
	public const TYPE_CAMEL = 'camel';

	/**
	 * For DB and form input/output.
	 *
	 * E.g. `my_field_name`
	 *
	 * @var string
	 */
	public const TYPE_UNDERSCORED = 'underscored';

	/**
	 * For query string usage.
	 *
	 * E.g. `my-field-name`
	 *
	 * @var string
	 */
	public const TYPE_DASHED = 'dashed';

	/**
	 * @var int|null
	 */
	protected $priority;

	/**
	 * @var \Cake\I18n\DateTime|string|int|null
	 */
	protected $notBefore;

	/**
	 * @var string|null
	 */
	protected $group;

	/**
	 * @var string|null
	 */
	protected $reference;

	/**
	 * @var string|null
	 */
	protected $status;

	/**
	 * @var array<string, array<string, string>>
	 */
	protected array $_keyMap = [
		self::TYPE_CAMEL => [
			'priority' => 'priority',
			'notbefore' => 'notBefore',
			'jobGroup' => 'group',
			'reference' => 'reference',
			'status' => 'status',
		],
		self::TYPE_UNDERSCORED => [
			'priority' => 'priority',
			'notbefore' => 'notBefore',
			'job_group' => 'group',
			'reference' => 'reference',
			'status' => 'status',
		],
		self::TYPE_DASHED => [
			'priority' => 'priority',
			'notbefore' => 'notBefore',
			'job-group' => 'group',
			'reference' => 'reference',
			'status' => 'status',
		],
	];

	/**
	 * @param array $data
	 * @param string|null $type
	 *
	 * @return $this
	 */
	public function fromArray(array $data, ?string $type = null) {
		$type = $this->keyType($type, static::TYPE_CAMEL);

		foreach ($data as $field => $value) {
			if ($type !== static::TYPE_CAMEL) {
				$field = $this->field($field, $type);
			}

			$this->$field = $value;
		}

		return $this;
	}

	/**
	 * @param string|null $type
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(?string $type = null): array {
		$fields = $this->fields();
		$type = $this->keyType($type);

		$values = [];
		foreach ($fields as $field) {
			$value = $this->$field;

			$key = $field;
			if ($type !== static::TYPE_CAMEL) {
				$key = $this->key($key, $type);
			}

			$values[$key] = $value;
		}

		return $values;
	}

	/**
	 * @return array<string>
	 */
	public function fields(): array {
		return array_values($this->_keyMap[static::TYPE_CAMEL]);
	}

	/**
	 * @param string|null $type
	 * @param string|null $default
	 *
	 * @return string
	 */
	protected function keyType(?string $type, ?string $default = null): string {
		if ($type === null) {
			$type = $default ?? static::TYPE_UNDERSCORED;
		}

		return $type;
	}

	/**
	 * @param string $key
	 * @param string $type
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string
	 */
	protected function key(string $key, string $type): string {
		$map = array_flip($this->_keyMap[$type]);
		if (!isset($map[$key])) {
			throw new InvalidArgumentException(sprintf('Invalid field lookup for type `%s`: `%s` does not exist.', $type, $key));
		}

		return $map[$key];
	}

	/**
	 * Lookup for dashed or underscored inflection of fields.
	 *
	 * @param string $name
	 * @param string $type Either dashed or underscored
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string
	 */
	public function field(string $name, string $type): string {
		if (!isset($this->_keyMap[$type][$name])) {
			throw new InvalidArgumentException(sprintf('Invalid field lookup for type `%s`: `%s` does not exist.', $type, $name));
		}

		return $this->_keyMap[$type][$name];
	}

	/**
	 * @param int|null $priority
	 *
	 * @return $this
	 */
	public function setPriority(?int $priority) {
		$this->priority = $priority;

		return $this;
	}

	/**
	 * @param int $priority
	 *
	 * @throws \RuntimeException If value is not present.
	 *
	 * @return $this
	 */
	public function setPriorityOrFail(int $priority) {
		$this->priority = $priority;

		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getPriority(): ?int {
		return $this->priority;
	}

	/**
	 * @throws \RuntimeException If value is not set.
	 *
	 * @return int
	 */
	public function getPriorityOrFail(): int {
		if ($this->priority === null) {
			throw new RuntimeException('Value not set for field `priority` (expected to be not null)');
		}

		return $this->priority;
	}

	/**
	 * @return bool
	 */
	public function hasPriority(): bool {
		return $this->priority !== null;
	}

	/**
	 * @param \Cake\I18n\DateTime|string|int|null $notBefore
	 *
	 * @return $this
	 */
	public function setNotBefore($notBefore) {
		$this->notBefore = $notBefore;

		return $this;
	}

	/**
	 * @param \Cake\I18n\DateTime|string|int|null $notBefore
	 *
	 * @throws \RuntimeException If value is not present.
	 *
	 * @return $this
	 */
	public function setNotBeforeOrFail($notBefore) {
		if ($notBefore === null) {
			throw new RuntimeException('Value not present (expected to be not null)');
		}
		$this->notBefore = $notBefore;

		return $this;
	}

	/**
	 * @return \Cake\I18n\DateTime|string|int|null
	 */
	public function getNotBefore() {
		return $this->notBefore;
	}

	/**
	 * @throws \RuntimeException If value is not set.
	 *
	 * @return \Cake\I18n\DateTime|string|int
	 */
	public function getNotBeforeOrFail() {
		if ($this->notBefore === null) {
			throw new RuntimeException('Value not set for field `notBefore` (expected to be not null)');
		}

		return $this->notBefore;
	}

	/**
	 * @return bool
	 */
	public function hasNotBefore(): bool {
		return $this->notBefore !== null;
	}

	/**
	 * @param string|null $group
	 *
	 * @return $this
	 */
	public function setGroup(?string $group) {
		$this->group = $group;

		return $this;
	}

	/**
	 * @param string $group
	 *
	 * @throws \RuntimeException If value is not present.
	 *
	 * @return $this
	 */
	public function setGroupOrFail(string $group) {
		$this->group = $group;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getGroup(): ?string {
		return $this->group;
	}

	/**
	 * @throws \RuntimeException If value is not set.
	 *
	 * @return string
	 */
	public function getGroupOrFail(): string {
		if ($this->group === null) {
			throw new RuntimeException('Value not set for field `group` (expected to be not null)');
		}

		return $this->group;
	}

	/**
	 * @return bool
	 */
	public function hasGroup(): bool {
		return $this->group !== null;
	}

	/**
	 * @param string|null $reference
	 *
	 * @return $this
	 */
	public function setReference(?string $reference) {
		$this->reference = $reference;

		return $this;
	}

	/**
	 * @param string $reference
	 *
	 * @throws \RuntimeException If value is not present.
	 *
	 * @return $this
	 */
	public function setReferenceOrFail(string $reference) {
		$this->reference = $reference;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getReference(): ?string {
		return $this->reference;
	}

	/**
	 * @throws \RuntimeException If value is not set.
	 *
	 * @return string
	 */
	public function getReferenceOrFail(): string {
		if ($this->reference === null) {
			throw new RuntimeException('Value not set for field `reference` (expected to be not null)');
		}

		return $this->reference;
	}

	/**
	 * @return bool
	 */
	public function hasReference(): bool {
		return $this->reference !== null;
	}

	/**
	 * @param string|null $status
	 *
	 * @return $this
	 */
	public function setStatus(?string $status) {
		$this->status = $status;

		return $this;
	}

	/**
	 * @param string $status
	 *
	 * @throws \RuntimeException If value is not present.
	 *
	 * @return $this
	 */
	public function setStatusOrFail(string $status) {
		$this->status = $status;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getStatus(): ?string {
		return $this->status;
	}

	/**
	 * @throws \RuntimeException If value is not set.
	 *
	 * @return string
	 */
	public function getStatusOrFail(): string {
		if ($this->status === null) {
			throw new RuntimeException('Value not set for field `status` (expected to be not null)');
		}

		return $this->status;
	}

	/**
	 * @return bool
	 */
	public function hasStatus(): bool {
		return $this->status !== null;
	}

}
