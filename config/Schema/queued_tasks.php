<?php
/**
 * Queued Tasks schema file
 *
 * @author David Yell <neon1024@gmail.com>
 * @author MGriesbach@gmail.com
 */

use Cake\Database\Schema\Table;

$t = new Table('queued_tasks');
$t->addColumn('id', [
	'type' => 'integer',
	'length' => 10,
	'null' => false,
	'default' => null,
]);
$t->addColumn('job_type', [
	'type' => 'string',
	'null' => false,
	'length' => 45,
]);
$t->addColumn('data', [
	'type' => 'text',
	'null' => true,
	'default' => null,
]);
$t->addColumn('job_group', [
	'type' => 'string',
	'length' => 255,
	'null' => true,
	'default' => null,
]);
$t->addColumn('reference', [
	'type' => 'string',
	'length' => 255,
	'null' => true,
	'default' => null,
]);
$t->addColumn('created', [
	'type' => 'datetime',
	'null' => true,
	'default' => null,
]);
$t->addColumn('notbefore', [
	'type' => 'datetime',
	'null' => true,
	'default' => null,
]);
$t->addColumn('fetched', [
	'type' => 'datetime',
	'null' => true,
	'default' => null,
]);
$t->addColumn('progress', [
	'type' => 'float',
	'length' => '3,2',
	'null' => true,
	'default' => null,
]);
$t->addColumn('status', [
	'type' => 'string',
	'length' => 255,
	'null' => true,
	'default' => null,
]);
$t->addColumn('completed', [
	'type' => 'datetime',
	'null' => true,
	'default' => null,
]);
$t->addColumn('failed', [
	'type' => 'integer',
	'null' => false,
	'default' => '0',
	'length' => 3,
]);
$t->addColumn('failure_message', [
	'type' => 'text',
	'null' => true,
	'default' => null,
]);
$t->addColumn('workerkey', [
	'type' => 'string',
	'null' => true,
	'length' => 45,
]);

$t->addConstraint('primary', [
	'type' => 'primary',
	'columns' => ['id'],
]);

$t->options([
	'collate' => 'utf8_unicode_ci',
]);
