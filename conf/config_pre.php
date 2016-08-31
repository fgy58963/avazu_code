<?php

$global_config_file = 'config_' . $global_config_files ['PRODUCTION'] . '.php';

// 公共的针对不同环境配制文件
require_once dirname ( __FILE__ ) . DS . $global_config_file;


if (!defined('LOG_LEVEL')) {
    define ('LOG_LEVEL', LOG_DEBUG);
}
if (!defined('LOG_SCRIBE_LEVEL')) {
    define ('LOG_SCRIBE_LEVEL', LOG_INFO);
}
