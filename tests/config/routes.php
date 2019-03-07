<?php

use Cake\Routing\Router;

Router::extensions(['json']);

require dirname(dirname(dirname(__FILE__))) . DS . 'config' . DS . 'routes.php';
