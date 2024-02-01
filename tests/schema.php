<?php
declare(strict_types=1);

use Cake\Utility\Inflector;

$tables = [];

/**
 * @var \DirectoryIterator<\DirectoryIterator> $iterator
 */
$iterator = new DirectoryIterator(__DIR__ . DS . 'Fixture');
foreach ($iterator as $file) {
	if (!preg_match('/(\w+)Fixture.php$/', (string)$file, $matches)) {
		continue;
	}

	$name = $matches[1];
	$tableName = null;
	$class = 'Queue\\Test\\Fixture\\' . $name . 'Fixture';
	try {
		$fieldsObject = (new ReflectionClass($class))->getProperty('fields');
		$tableObject = (new ReflectionClass($class))->getProperty('table');
		$tableName = $tableObject->getDefaultValue();
	} catch (ReflectionException $e) {
		continue;
	}

	if (!$tableName) {
		$tableName = Inflector::underscore($name);
	}

	$array = $fieldsObject->getDefaultValue();
	$constraints = $array['_constraints'] ?? [];
	$indexes = $array['_indexes'] ?? [];
	unset($array['_constraints'], $array['_indexes'], $array['_options']);
	$table = [
		'table' => $tableName,
		'columns' => $array,
		'constraints' => $constraints,
		'indexes' => $indexes,
	];
	$tables[$tableName] = $table;
}

return $tables;
