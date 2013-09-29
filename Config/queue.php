<?php

$config['Queue'] = array(
	'sleeptime' => 10,
	'gcprop' => 10,
	'defaultworkertimeout' => 120,
	'defaultworkerretries' => 4,
	'workermaxruntime' => 0,
	'cleanuptimeout' => 2000,
	'exitwhennothingtodo' => false,
	'pidfilepath' => TMP . 'queue' . DS,
	'log' => true,
	'notify' => 'tmp' // Set to false to disable (tmp = file in TMP dir)
);
