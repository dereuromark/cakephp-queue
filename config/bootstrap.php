<?php
use Cake\Core\Configure;
use Queue\Generator\Task\QueuedJobTask;

// Optionally load additional queue config defaults from local app config
if (file_exists(ROOT . DS . 'config' . DS . 'app_queue.php')) {
	Configure::load('app_queue');
}

// For IdeHelper plugin if in use - make sure to run `bin/cake phpstorm generate` then
$generatorTasks = (array)Configure::read('IdeHelper.generatorTasks');
$generatorTasks[] = QueuedJobTask::class;
Configure::write('IdeHelper.generatorTasks', $generatorTasks);
