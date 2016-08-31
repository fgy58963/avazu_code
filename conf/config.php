<?php

/**
 * 全局配制接口文件
 *
 * 所有与环境相关的配制都集中放在一个配制文件中
 * config_development.php 开发环境的配制文件
 * config_production.php 线上环境的配制文件
 * config_test.php 测试环境的配制文件
 *
 * 程序根据当前服务器的hostname自动读取当前环境的配制文件
 *
 **/
class Config
{
    private static $CONFIG = array();

    /**
     * 添加配制数组
     *
     * @param $config array
     * @return void
     */
    public static function add($config)
    {
        self::$CONFIG = self::_merge($config, self::$CONFIG);
    }

    private static function _merge($source, $target)
    {
        foreach ($source as $key => $val) {
            if (!is_array($val) || !isset ($target [$key])) {
                $target [$key] = $val;
            } else {
                $target [$key] = self::_merge($val, $target [$key]);
            }
        }
        return $target;
    }

    public static function set($key, $val)
    {
        $config = &self::$CONFIG;
        $segments = explode('.', $key);
        $key = array_pop($segments);
        foreach ($segments as $segment) {
            if (!isset ($config [$segment])) {
                $config [$segment] = array();
            }
            $config = &$config [$segment];
        }
        $config [$key] = $val;
    }

    /**
     * 获取一个配制值
     *
     * @param string $key
     *            配制名, 可包含多级，用 "." 分隔
     * @param string $default
     *            default NULL,默认值
     * @return mixed
     */
    public static function get($key, $default = NULL)
    {
        $config = self::$CONFIG;
        if (is_null($key) || $key === '') {
            return $config;
        }

        $path = explode('.', $key);
        foreach ($path as $key) {
            $key = trim($key);
            if (empty ($config) || !isset ($config [$key])) {
                return $default;
            }
            $config = $config [$key];
        }

        return $config;
    }


    public static function getDbSingleConfig ($dbKey){

        // d_limesurvey
        $db_single = Config::get('db_singles.'.$dbKey);

        $db_physical = Config::get('db_physical.'.$db_single['map']);

        $ret_array = array();
        $ret_array['host'] = $db_physical['write']['host']; 
        $ret_array['port'] = $db_physical['write']['port']; 
        $ret_array['db_user'] = $db_physical['db_user']; 
        $ret_array['db_pwd'] = $db_physical['db_pwd']; 
        $ret_array['db_name'] = $db_single['db_name']; 

        return $ret_array;
    }    

    /**
     * Alias of method get
     */
    public static function g($key, $default = NULL)
    {
        return self::get($key, $default);
    }
}

require dirname(__FILE__) . DS . 'constants.php';


//设置全局参数
Config::add(array(
   'db_physical' => array( //physical master-slave shard configuration
       0 => array(
           'write' => array(
               'host' => '192.168.3.222',
               'port' => 3306
           ),
           'read' => array(
               array(
                   'host' => '192.168.3.222',
                   'port' => 3306,
                   'weight' => 10
               ),
           ),
           'db_user' => 'root',
           'db_pwd' => '123456'
       ),
   ),
   // mysql -h'148.251.10.20' -u'superflashlight' -p'Fs&48#PnBv'
    
    /**
     * 用户安装数据表的分库分表
     */
    'db_cluster' => array(
        // 'd_analytics_user_install' => array(
        //     'farm_policy' => 'partition_by_char',
        //     'db_name_prefix' => 'd_analytics_user_install_',
        //     'farm_id_converter' => 'partition_by_char',
        //     'map' => array(
        //         '0' => 0,
        //         '1' => 0, 
        //         '2' => 0,
        //         '3' => 0, 
        //         '4' => 0,
        //         '5' => 0, 
        //         '6' => 0,
        //         '7' => 0, 
        //         '8' => 0,
        //         '9' => 0, 
        //         'a' => 0,
        //         'b' => 0, 
        //         'c' => 0,
        //         'd' => 0,
        //         'e' => 0, 
        //         'f' => 0, 
        //     ),
        //),
    ),

    'db_singles' => array(
        // 'd_analytics_event' => array(
        //     'map' => 3,
        //     'db_name' => 'd_analytics_event',
        // ),
        'd_analytics_report' => array(
            'map' => 0,
            'db_name' => 'd_analytics_report',
        ),
    ),

    'cache_physical' => array(
        0 => array(
            'host' => '127.0.0.1',
            'port' => 11211,
        ),
    ),
    
    'mail' => array(
        'type' => 'smtp',
        'smtp' => 'smtp.avazu.net',
        'user' => 'system-noreply@avazu.net',
        'pwd' => 'Ap(PVp>t',
        'name' => 'SystemEngine',
        'secret_key' => '1243Sdqq123c~',
    ),

    //  各业务参数单独在这里配置 Config::get('params.payments')
    "params" =>  require(__DIR__.'/config_params.php'),

    "hadoop_path" => '/opt/htdocs/www/hadoop.superflashlight.mobi',
    'token_path' => PATH_ROOT . '/scripts/cronjobs/app/Conf/Facebook.d',
));

require dirname(__FILE__) . DS . 'config_env.php';

$global_config_file = 'config_' . $global_config_files [ENV] . '.php';

// 公共的针对不同环境配制文件
require_once dirname(__FILE__) . DS . $global_config_file;

// 每个应用独立的配制文件
if (defined('APP_PATH_CONF')) {
    if (file_exists(APP_PATH_CONF . DS . 'config.php')) {
        require APP_PATH_CONF . DS . 'config.php';
    }

    if (file_exists(APP_PATH_CONF . DS . $global_config_file)) {
        require APP_PATH_CONF . DS . $global_config_file;
    }

    if (file_exists(APP_PATH_CONF . DS . 'constants.php')) {
        require APP_PATH_CONF . DS . 'constants.php';
    }
}

if (defined('TEST_CONF_FILE')) {
    require TEST_CONF_FILE;
}

