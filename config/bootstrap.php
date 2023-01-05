<?php

use Cake\Core\Configure;
use Queue\Generator\Task\QueuedJobTask;

// For IdeHelper plugin if in use - make sure to run `bin/cake phpstorm generate` then
$generatorTasks = (array)Configure::read('IdeHelper.generatorTasks');
$generatorTasks[] = QueuedJobTask::class;
Configure::write('IdeHelper.generatorTasks', $generatorTasks);
