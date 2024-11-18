<?php

namespace Queue\Model\Behavior;

use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use InvalidArgumentException;
use RuntimeException;
use Shim\Database\Type\ArrayType;

/**
 * A behavior that will json_encode (and json_decode) fields if they contain an array or specific pattern.
 *
 * @author Mark Scherer
 * @license MIT
 */
class JsonableBehavior extends Behavior {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'fields' => [], // Fields to convert
		'input' => 'array', // json, array, param, list (param/list only works with specific fields)
		'output' => 'array', // json, array, param, list (param/list only works with specific fields)
		'separator' => '|', // only for param or list
		'keyValueSeparator' => ':', // only for param
		'leftBound' => '{', // only for list
		'rightBound' => '}', // only for list
		'map' => [], // map on a different DB field
		'encodeParams' => [ // params for json_encode
			'options' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
			'depth' => 512,
		],
		'decodeParams' => [ // params for json_decode
			'assoc' => true, // useful when working with multidimensional arrays
			'depth' => 512,
			'options' => JSON_THROW_ON_ERROR,
		],
	];

	/**
	 * @param array $config
	 *
	 * @throws \RuntimeException
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		if (empty($this->_config['fields'])) {
			throw new RuntimeException('Fields are required');
		}
		if (!is_array($this->_config['fields'])) {
			$this->_config['fields'] = (array)$this->_config['fields'];
		}
		if (!is_array($this->_config['map'])) {
			$this->_config['map'] = (array)$this->_config['map'];
		}
		if (!empty($this->_config['map']) && count($this->_config['fields']) !== count($this->_config['map'])) {
			throw new RuntimeException('Fields and Map need to be of the same length if map is specified.');
		}
		foreach ($this->_config['fields'] as $field) {
			if ($this->_table->getSchema()->getColumnType($field) !== 'json') {
				$this->_table->getSchema()->setColumnType($field, 'json');
			}
		}
		if ($this->_config['encodeParams']['options'] === null) {
			$options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_ERROR_INF_OR_NAN | JSON_PARTIAL_OUTPUT_ON_ERROR;
			$this->_config['encodeParams']['options'] = $options;
		}

		TypeFactory::map('array', ArrayType::class);
	}

	/**
	 * Decode the fields on after find
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @param bool $primary
	 *
	 * @return void
	 */
	public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary) {
		$query->formatResults(function (CollectionInterface $results) {
			return $results->map(function ($row) {
				if (!$row instanceof Entity) {
					return $row;
				}

				$this->decodeItems($row);

				return $row;
			});
		});
	}

	/**
	 * Decodes the fields of an array/entity (if the value itself was encoded)
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return void
	 */
	public function decodeItems(EntityInterface $entity) {
		$fields = $this->_getMappedFields();

		foreach ($fields as $map => $field) {
			$val = $entity->get($field);
			if (is_string($val)) {
				$val = $this->_fromJson($val);
			}
			$entity->set($map, $this->_decode($val));
		}
	}

	/**
	 * Saves all fields that do not belong to the current Model into 'with' helper model.
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 *
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options) {
		$fields = $this->_getMappedFields();

		foreach ($fields as $map => $field) {
			if ($entity->get($map) === null) {
				continue;
			}
			$val = $entity->get($map);
			$entity->set($field, $this->_encode($val));
		}
	}

	/**
	 * @return array
	 */
	protected function _getMappedFields() {
		$usedFields = $this->_config['fields'];
		$mappedFields = $this->_config['map'];
		if (!$mappedFields) {
			$mappedFields = $usedFields;
		}

		$fields = [];

		foreach ($mappedFields as $index => $map) {
			if (!$map || $map == $usedFields[$index]) {
				$fields[$usedFields[$index]] = $usedFields[$index];

				continue;
			}
			$fields[$map] = $usedFields[$index];
		}

		return $fields;
	}

	/**
	 * @param array|string $val
	 *
	 * @return string|null
	 */
	public function _encode($val) {
		if (!empty($this->_config['fields'])) {
			if ($this->_config['input'] === 'json') {
				if (!is_string($val)) {
					throw new InvalidArgumentException('Only accepts JSON string for input type `json`');
				}
				$val = $this->_fromJson($val);
			}
		}
		if (!is_array($val)) {
			return null;
		}

		$result = json_encode($val, $this->_config['encodeParams']['options'], $this->_config['encodeParams']['depth']);
		if ($result === false) {
			return null;
		}

		return $result;
	}

	/**
	 * Fields are absolutely necessary to function properly!
	 *
	 * @param array|null $val
	 *
	 * @return string|null
	 */
	public function _decode($val) {
		if (!is_array($val)) {
			return null;
		}

		$flags = $this->_config['encodeParams']['options'] | JSON_PRETTY_PRINT;
		$decoded = json_encode($val, $flags, $this->_config['decodeParams']['depth']);
		if ($decoded === false) {
			return null;
		}

		return $decoded;
	}

	/**
	 * @param string $val
	 *
	 * @return array
	 */
	protected function _fromJson(string $val): array {
		$json = json_decode($val, true, JSON_THROW_ON_ERROR);

		return $json;
	}

}
