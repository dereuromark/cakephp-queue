<?php

// Optionally load additional queue config defaults
// from local app config
if (file_exists(APP . 'Config' . DS . 'queue.php')) {
	Configure::load('queue');
}
