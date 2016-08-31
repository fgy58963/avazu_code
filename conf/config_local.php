<?php

$global_config_file = 'config_' . $global_config_files ['DEVELOPMENT'] . '.php';

// 公共的针对不同环境配制文件
require_once dirname ( __FILE__ ) . DS . $global_config_file;


Config::add(array(
    	'cache_physical'   => array(
		0 => array(
			'host' => '127.0.0.1',
			'port' => 11211,
		),
	),
));

	