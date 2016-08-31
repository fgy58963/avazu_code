<?php
class Cimongo_GlobalCM
{
    protected static $_instances;

    protected function __construct(
    )
    {
    }
    
    /**
     * 获取global db 对象
     *
     * @param integer $clusterId
     * @param boolean $singleton 是否使用单例模式
     * @return object Db_GlobalDb
     */
    public static function & getInstance($clusterId, $singleton = TRUE)
    {
        $dbGlobals = Config::get('mongo_singles');
        
        if (empty($dbGlobals[$clusterId])) {
			$param = FALSE;
            return $param;
        }

        //以全局设置优先
        $singleton = defined('CMONGO_BASE_SINGLETON') ? CMONGO_BASE_SINGLETON : $singleton;

        $dbName = $dbGlobals[$clusterId]['db_name'];
        if (($singleton == TRUE && empty(self::$_instances[$clusterId][$dbName])) || $singleton == FALSE) {
            $phyConfig = Config::get('mongo_physical');
            $config = $phyConfig[$dbGlobals[$clusterId]['map']];
            
            if (isset($dbGlobals[$clusterId]['db_user'])) {
                $dbUser = $dbGlobals[$clusterId]['db_user'];
            }
            else if (isset($config['write']['db_user'])) {
                $dbUser = $config['write']['db_user'];
            } else if (isset($config['db_user'])) {
                $dbUser = $config['db_user'];
            } else {
                $dbUser = $phyConfig['db_user'];
            }

            if (isset($dbGlobals[$clusterId]['db_pwd'])) {
                $dbPwd = $dbGlobals[$clusterId]['db_pwd'];
            }
            else if (isset($config['write']['db_pwd'])) {
                $dbPwd = $config['write']['db_pwd'];
            } else if (isset($config['db_pwd'])) {
                $dbPwd = $config['db_pwd'];
            } else {
                $dbPwd = $phyConfig['db_pwd'];
            }
            // $db = new self(
            //     $dbName, $dbUser, $dbPwd,
            //     $config['read'],
            //     $config['write']
            // );

            $db = array(
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'db_name' => $dbName,
                    'db_user' => $dbUser,
                    'db_pwd' => $dbPwd,
                    'query_safety' => $dbGlobals[$clusterId]['query_safety'],
                    'sess_use_mongo'=> $dbGlobals[$clusterId]['sess_use_mongo'],
                    'sess_collection_name'=>$dbGlobals[$clusterId]['sess_collection_name'],
                    'mongo_return' => $dbGlobals[$clusterId]['mongo_return'],
                );
            self::$_instances[$clusterId][$dbName] = $db;
        }

        return self::$_instances[$clusterId][$dbName];
    }
}
