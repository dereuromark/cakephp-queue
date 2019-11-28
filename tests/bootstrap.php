<?php
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\Cache\Cache;
use Cake\Mailer\TransportFactory;
use Cake\Datasource\ConnectionManager;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', ROOT . DS . 'tests' . DS . 'test_app' . DS . 'src' . DS);
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);

define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('CONFIG', __DIR__ . DS . 'config' . DS);
define('TESTS', __DIR__ . DS);

ini_set('intl.default_locale', 'de-DE');

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';

Configure::write('App', [
	'namespace' => 'App',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS],
	],
]);

Configure::write('debug', true);

Configure::write('EmailTransport', [
		'default' => [
			'className' => 'Debug',
		],
]);
Configure::write('Email', [
		'default' => [
			'transport' => 'default',
			'from' => 'you@localhost',
		],
]);

mb_internal_encoding('UTF-8');

$Tmp = new Folder(TMP);
$Tmp->create(TMP . 'cache/models', 0770);
$Tmp->create(TMP . 'cache/persistent', 0770);
$Tmp->create(TMP . 'cache/views', 0770);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cache::setConfig($cache);

Cake\Core\Plugin::getCollection()->add(new Queue\Plugin());

TransportFactory::setConfig('default', [
	'className' => 'Debug',
]);
TransportFactory::setConfig('queue', [
	'className' => 'Queue.Queue',
]);
/*
Cake\Mailer\TransportFactory::setConfig('default', [
	'transport' => 'default',
]);
*/

// Allow local overwrite
// E.g. in your console: export db_dsn="mysql://root:secret@127.0.0.1/cake_test"
if (!getenv('db_class') && getenv('db_dsn')) {
	ConnectionManager::setConfig('test', ['url' => getenv('db_dsn')]);
	return;
}
if (!getenv('db_class')) {
	putenv('db_class=Cake\Database\Driver\Sqlite');
	putenv('db_dsn=sqlite::memory:');
}

// Uses Travis config then (MySQL, Postgres, ...)
ConnectionManager::setConfig('test', [
	'className' => 'Cake\Database\Connection',
	'driver' => getenv('db_class') ?: null,
	'dsn' => getenv('db_dsn') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,

]);
