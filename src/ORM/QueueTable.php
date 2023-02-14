<?php
declare(strict_types=1);

namespace Queue\ORM;

use Cake\ORM\Table;

class QueueTable extends Table {

	/**
	 * @inheritDoc
	 */
	public function setTable(string $table): Table {
		$parts = [$table];
		$schema = $this->getConnection()->config()['schema'] ?? null;
		if ($schema) {
			array_unshift($parts, $schema);
		}

		return parent::setTable(implode('.', $parts));
	}

}
