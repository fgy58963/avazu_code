<?php
if ( ! defined('STAT_ER_DATABASE')) {
    //数据库错误
    define('STAT_ER_DATABASE', 'stat_error_database');
}



if ( ! defined('STAT_ET_HTTP_CONNECT')) {
    define('STAT_ET_HTTP_CONNECT', 'stat_et_http_connect');
}


if ( ! defined('STAT_ET_DB_CONNECT')) {
    //数据库连接时间
    define('STAT_ET_DB_CONNECT', 'stat_et_db_connect');
}

if ( ! defined('STAT_ET_DB_QUERY')) {
    //数据库查询耗时
    define('STAT_ET_DB_QUERY', 'stat_et_db_query');
}

if ( ! defined('STAT_ER_MEMCACHE')) {
    //memcache连接错误
    define('STAT_ER_MEMCACHE', 'stat_error_memcache');
}

if ( ! defined('STAT_ET_MEMCACHE_CONNECT')) {
    //Memcache连接时间
    define('STAT_ET_MEMCACHE_CONNECT', 'stat_et_memcache_connect');
}

if ( ! defined('STAT_ET_MONGO_CONNECT')) {
    //Memcache连接时间
    define('STAT_ET_MONGO_CONNECT', 'stat_et_mongo_connect');
}

if ( ! defined('STAT_ER_REDIS')) {
    define('STAT_ER_REDIS', 'stat_er_redis');
}

if ( ! defined('STAT_ET_REDIS')) {
    define('STAT_ET_REDIS', 'stat_et_redis');
}

//js,css 静态资源有调整请修改这个版本号
define('STATIC_CDN_VERSION', '201603102145');

//默认平台
if ( ! defined('DEFAULT_PLATFORM_ID')) {
    define('DEFAULT_PLATFORM_ID' , '0');
}

//另外一个平台
if ( ! defined('OTHER_PLATFORM_ID')) {
    define('OTHER_PLATFORM_ID' , '10');
}

//不要使用
if ( ! defined('DEFAULT_PLATFROM_ID')) {
    define('DEFAULT_PLATFROM_ID' , '0');
}

//默认渠道id
if ( ! defined('DEFAULT_TRAFFIC_ID')) {
    define('DEFAULT_TRAFFIC_ID' , '1');
}