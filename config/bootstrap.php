<?php
use Cake\Core\Configure;

// Optionally load additional queue config defaults
// from local app config

if (file_exists(ROOT . DS . 'config' . DS . 'queue.php')) {
	Configure::load('queue');
}
