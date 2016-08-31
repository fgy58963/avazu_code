<?php
class Db_GlobalDb extends Db_Base
{
    protected static $_instances;

    protected function __construct(
        $dbName, $dbUser, $dbPwd, $readServers, $writeServers
    )
    {
        parent::__construct(
            $dbName, $dbUser, $dbPwd, $readServers, $writeServers
        );
    }

    /**
     * 获取global db 对象
     *
     * @param integer $clusterId
     * @param boolean $singleton 是否使用单例模式
     * @return object Db_GlobalDb
     */
    // public static function & getInstance($dbName, $dbUser, $dbPwd, $readServers, $writeServers)
    public static function & getInstance($clusterId, $singleton = TRUE,$x1=null,$x2=null,$x3=null)
    {
        $dbGlobals = Config::get('db_singles');

        if (empty($dbGlobals[$clusterId])) {
            $param = FALSE;
            return $param;
        }

        //以全局设置优先
        $singleton = defined('DB_BASE_SINGLETON') ? DB_BASE_SINGLETON : $singleton;

        $dbName = $dbGlobals[$clusterId]['db_name'];
        if (($singleton == TRUE && empty(self::$_instances[$clusterId][$dbName])) || $singleton == FALSE) {
            $phyConfig = Config::get('db_physical');
            $config = $phyConfig[$dbGlobals[$clusterId]['map']];
            if (isset($config['write']['db_user'])) {
                $dbUser = $config['write']['db_user'];
            } else if (isset($config['db_user'])) {
                $dbUser = $config['db_user'];
            } else {
                $dbUser = $phyConfig['db_user'];
            }
            if (isset($config['write']['db_pwd'])) {
                $dbPwd = $config['write']['db_pwd'];
            } else if (isset($config['db_pwd'])) {
                $dbPwd = $config['db_pwd'];
            } else {
                $dbPwd = $phyConfig['db_pwd'];
            }
            $db = new self(
                $dbName, $dbUser, $dbPwd,
                $config['read'],
                $config['write']
            );
            self::$_instances[$clusterId][$dbName] = $db;
        }

        return self::$_instances[$clusterId][$dbName];
    }
}
