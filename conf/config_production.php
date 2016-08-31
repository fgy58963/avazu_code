<?php

//从预发布里继承
// $global_config_file = 'config_' . $global_config_files ['PRE'] . '.php';
// 公共的针对不同环境配制文件
// require_once dirname ( __FILE__ ) . DS . $global_config_file;

Config::add(array(
    'db_physical' => array( //physical master-slave shard configuration
        0 => array(
            'write' => array(
                'host' => '148.251.10.20',
                'port' => 3306
            ),
            'read' => array(
                array(
                    'host' => '148.251.10.20',
                    'port' => 3306,
                    'weight' => 10
                ),
            ),
              'db_user' => 'superflashlight',
              'db_pwd' => 'Fs&48#PnBv'
        ),
    ),
   
    'cache_physical' => array(
        0 => array(
            'host' => '148.251.10.76',
            'port' => 11211,
        ),
    ),
  
    'cache_cluster' => array(
        'default' => array(
            0 , 4
        ),
    ),
    'all_fb_users' => array(
        '12087752@qq.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1638954679682946'),
        ),
        '2850909726@qq.com' => array(
            'password' => 'Xian22For07',
            'appIds' => array('1605996809674261'),
        ),
        'chenyiping5362@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('195397060815689'),
        ),
        'dotccoop409@gmail.com' => array(
            'password' => 'JpL2Bnt5',
            'appIds' => array('1447264415577075'),
        ),
        'flashkeyboard2015@gmail.com' => array(
            'password' => 'avazu1021',
            'appIds' => array('1050568435005411'),
        ),
        'fingerfeed@avazu.net' => array(
            'password' => 'o1Wzn11o',
            'appIds' => array('838598546190390'),
        ),
        'jiacenye1021@gmail.com' => array(
            'password' => 'avazu1021',
            'appIds' => array('1542807559327931'),
        ),
        'lianglei3313@gmail.com' => array(
            'password' => 'avazu315',
            'appIds' => array('941706862544032','1733536653558644'),
        ),
        'lijun3819@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('170529713328855'),
        ),
        'lili422202@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1097101966988292'),
        ),
        'lionmay775@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('832323060247332','911343095640881'),
        ),
        'liyun1086@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1574066282920165'),
        ),
        'mahaoran4834@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('190357891337733'),
        ),
        'swiftkeyboardteam@gmail.com' => array(
            'password' => 'flashkeyboard2016',
            'appIds' => array('731317793634706'),
        ),
        'swiftbrowser2015@gmail.com' => array(
            'password' => 'avazu1021',
            'appIds' => array('1021774097908520'),
        ),
        'xiongxintong2847@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('951536368265123'),
        ),
        'yangkai2432@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('751014151667346','1744202812458907'),
        ),
        'zongjun9361@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1713507248906238'),
        ),
        'zhujunruo6424@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1021585187955793'),
        ),
        'gaoshuyang4017@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('191329304603235'),
        ),
        'chenming350681@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1803322296555729'),
        ),
        'zhoulin2214@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('1029591527116937'),
        ),
        'yinbo0077@gmail.com' => array(
            'password' => 'avazu123',
            'appIds' => array('299285990410579'),
        ),
    )
));

if(!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', LOG_INFO);
}