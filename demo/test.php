<?php
require('../src/httpLimit.php');

$limit=new httpLimit();
$limit->setConfig(
	array(
		'server'=>array(
			'host'=>'127.0.0.1',
			'port'=>11211,
		),
		'keys'=>array(
			'REQUEST_URI',
			'REMOTE_ADDR',
		),
		'limits'=>array(
			// Limit count of refreshes for page per lifetime
			'default'=>20,
			'REQUEST_URI'=>array(
				// Set higher limit for particular URI
				'/demo/test.php?x=more'=>1000,
			)
		),
		'lifetime'=>60,
		'resolution'=>5,
	)
);
$ret=$limit->run();
print_r($ret);
