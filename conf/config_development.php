<?php
Config::add(array(
	'db_physical'      => array( //physical master-slave shard configuration
		0 => array(
			'write'   => array(
				'host' => '192.168.3.222',
				'port' => 3306,
			),
			'read'    => array(
				array(
					'host'   => '192.168.3.222',
					'port'   => 3306,
					'weight' => 10,
				),
			),
			'db_user' => 'root',
			'db_pwd'  => '123456',
		),
	),
	'cache_physical'   => array(
		0 => array(
			'host' => '192.168.3.222',
			'port' => 11211,
		),
	),
	"hadoop_path" => PATH_DATA . '/hadoop',
	'all_fb_users' => array(
        '12087752@qq.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1638954679682946'),
        ),
    ),
)

);

if (!defined('LOG_LEVEL')) {
	define('LOG_LEVEL', LOG_DEBUG);
}
if (!defined('LOG_SCRIBE_LEVEL')) {
	define('LOG_SCRIBE_LEVEL', LOG_INFO);
}

error_reporting(E_ALL);
//ini_set('display_errors', 'on');