<?php
$global_config_files = array(
    'LOCAL' => 'local',
    'DEVELOPMENT' => 'development',
    'TEST' => 'test',
    'PRODUCTION' => 'production',
    'PRODUCTION_NEW' => 'production_new',
    'PRE' => 'pre'
);

if (!defined('ENV')) {
    $hostname = php_uname('n');

    $localHostnames = array(
        'ggg-MacBook-Air.local',
        'local',
        'AVAZU-PC059'
    );
    $devHostnames = array(
        'appdev',
        'teebik-dev',
        'aa-dev-php-193',
        'baboo.local',
        'zz-VirtualBox',
        'wflmde',
        'aa-dev-php-237',
        'localhost.localdomain',
        'dev',
        'HP-PC',
        'AVAZU-PC067',
        'AVAZU-FRANK-WWJ'
    );
    $testHostnames = array (
        // 'ggg-MacBook-Air.local',
        'teebik-dev',
        'aa-test-php-194',
        'avazu.com.cn'
    );
    $preHostnames = array(
        'db22111-mysql-node11.idc.avazu.net'
        // 'ggg-MacBook-Air.local',
    );

    if (in_array($hostname, $localHostnames)) {
        define ('ENV', 'LOCAL');
    } else if (in_array($hostname, $devHostnames)) {
        define ('ENV', 'DEVELOPMENT');
    } else if (in_array($hostname, $testHostnames)) {
        define ('ENV', 'TEST');
    } else if (in_array($hostname, $preHostnames)) {
        define ('ENV', 'PRE');
    } 
    else {
        define ('ENV', 'PRODUCTION');
    }
}