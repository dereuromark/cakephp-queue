<?php

$config['queue'] = array(
	'sleeptime' => 10,
	'gcprop' => 10,
	'defaultworkertimeout' => 180,
	'defaultworkerretries' => 4,
	'workermaxruntime' => 0,
	'cleanuptimeout' => 2000,
	'exitwhennothingtodo' => false,
	'log' => true,
	'notify' => 'tmp'
);
