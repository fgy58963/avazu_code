<?php
Class Db_Redis 
{
    public static $PERSISTENT_CONNECT = TRUE;
    public static $TIMEOUT = 5; 
    public static $ISCONNECT = TRUE;
    protected $_retrySleepMicroSecond = 1000000;

    protected $_client = NULL;
    
    protected static $_instances = array();

    private function __construct()
    {
        $this->_client = new Redis();  
    }

    public function __call ($name, $arguments)
    {
        if ( ! $this->_client) {
            log_message('Redis client invalid!', LOG_ERR);
            return FALSE;
        }
        
        if ( ! method_exists($this->_client, $name)) {
            trigger_error("The method \"$name\" not exists for Redis object.", E_USER_ERROR);
            return FALSE;
        }
        
        if (empty($arguments)) {
            $arguments = array();
        }
        
        try {
            ETS::start(STAT_ET_REDIS);
            $ret = call_user_func_array(array($this->_client, $name), $arguments);
            ETS::end(STAT_ET_REDIS, "method:$name");
        } catch (Exception $e) {
            $ret = $this->_retry($name,$arguments);
        }

        if ($ret === FALSE && in_array($name, array('open', 'connect', 'popen', 'pconnect'))) {
            self::$ISCONNECT = FALSE;
			error_report(STAT_ER_REDIS, "REDIS connect error:{$arguments[0]}:{$arguments[1]}");
        } else {
            self::$ISCONNECT = TRUE;
        }
        return $ret;
    }
    
    /**
     * 连接断开后,重试一次
     * phpredis throws a RedisException object if it can't reach the Redis server. 
     * That can happen in case of connectivity issues, if the Redis service is down, or if the redis host is overloaded.
     */
    protected function _retry($name, $arguments)
    {
        log_message("Db_Redis retry usleep {$this->_retrySleepMicroSecond} micro second", LOG_WARNING);
        usleep($this->_retrySleepMicroSecond);
        try {
            ETS::start(STAT_ET_REDIS);
            $ret = call_user_func_array(array($this->_client, $name), $arguments);
            ETS::end(STAT_ET_REDIS, "method:$name");
        } catch (Exception $e) {
            $ret = FALSE;
            log_message("Db_Redis retry Redis exception:" . $e, LOG_ERR);
        }
        log_message("Db_Redis retry result:" . json_encode($ret), LOG_WARNING);
        return $ret;
    }

    public function __destruct()
    {
        if ($this->_client) {
            if (self::$ISCONNECT){
                @$this->_client->close();
            }
        }
    }
    
    public static function getInstance($clusterId = 'default')
    {
        if (isset(self::$_instances[$clusterId])) {
            return self::$_instances[$clusterId];
        }

        $config = Config::get("redis_single.{$clusterId}"); 

        if (empty($config)) {
            trigger_error("Config error:no redis cluster config $clusterId", E_USER_ERROR);
            return NULL;
        }

        list($map, $db) = explode('.', $config);

        $physicalConfig = Config::get("redis_physical.{$map}");
        if (empty($physicalConfig)) {
            trigger_error("Config error:no redis physical config $map", E_USER_ERROR);
            return NULL;
        }
        $host = $physicalConfig['host'];
        $port = $physicalConfig['port'];
        $client = new self();
        $connectRet = TRUE;
        if (self::$PERSISTENT_CONNECT) {
            $connectRet = $client->pconnect($host, $port, self::$TIMEOUT); 
        } else {
            $connectRet = $client->connect($host, $port, self::$TIMEOUT);
        }
        if ( ! $connectRet) {
            return NULL;
            //throw error?    
        }
        $client->select($db);

        self::$_instances[$clusterId] = $client;
        return self::$_instances[$clusterId];
    }

    public function setRetrySleepMicroSecond($microSecond)
    {
        $this->_retrySleepMicroSecond = $microSecond;
    }
}
