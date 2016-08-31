<?php
/**
 * 此类用户获取Web运行时全局变量配置信息
 * 例如：登陆用户的相关信息
 */
class AppRunner
{
    const EXCLUDE_HOST_NAME = 'api.gogobuy.info';

    public static $config = array(
        'email' => '',
        'http_host'    => '',
        'sso_uid'       => '',
        'islog'     =>false,
        'ip' => ''
    );

    /**
     * 根据域名初始化全局配合变量
     */
    public function init($sso_user,$islog) {
        if (isset($sso_user['user_id'])) {
            self::$config['sso_uid'] = $sso_user['user_id'];
        }
        self::$config['http_host'] = $this->getHttpHost();
        if (self::$config['http_host'] == self::EXCLUDE_HOST_NAME) {
            return;
        }
        if (isset($sso_user['email'])) {
            self::$config['email'] = $sso_user['email'];
        }
        if($islog){
            self::$config['islog'] = true;
        }
        self::$config['ip'] = ip2long(get_client_ip());
        return TRUE;
    }

    public function getHttpHost() {
        if (!self::$config['http_host']) {
            self::$config['http_host'] = trim($_SERVER['HTTP_HOST']);
        }
    
        return self::$config['http_host'];
    }
}