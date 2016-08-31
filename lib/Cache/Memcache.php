<?php
/**
 * Memcache类的二次封装，以支持Memcache的cluster配制
 */
class Cache_Memcache
{
    protected static $_objList;
    private $_memcache;
    private $_clusterId;
    private $_serverId;
    private static $_boolPersistent = TRUE;
    
    const FLAGS = MEMCACHE_COMPRESSED; //压缩数据
    
    /* No memcache service */
    const NULL_CLUSTER_ID = 'NULL_CLUSTER_ID';

    private function __construct($clusterId, $serverId = NULL)
    {
        $this->_clusterId = $clusterId;
        $this->_serverId = $serverId;
    }

    private function init()
    {
        $clusterId = $this->_clusterId;

        if ($clusterId == self::NULL_CLUSTER_ID) {
            return FALSE;
        }

        $cacheConfig = Config::get('cache_cluster');
        $memcacheHosts = $cacheConfig[$clusterId];
        
        //Specify the server
        if ($this->_serverId !== NULL) {
            $memcacheHosts = array($this->_serverId);
        }
        
        if (empty($memcacheHosts)) {
            //Error log
            return FALSE;
        } else {
            $this->_memcache = new Memcache;
            $phyHosts = Config::get('cache_physical');
            foreach ($memcacheHosts as $phyHostId) {
                $weight = isset($phyHosts[$phyHostId]['weight']) ? $phyHosts[$phyHostId]['weight'] : 10; 
                $this->_memcache->addServer(
                    $phyHosts[$phyHostId]['host'],
                    $phyHosts[$phyHostId]['port'],
                    self::$_boolPersistent,
                    $weight
                );
            }
        }
    }
    
    /**
     * 获取一个Cache_Memcache实例
     *
     * @param integer $clusterId cluster id
     * @return object
     */
    public static function & getInstance($clusterId, $serverId = NULL)
    {
        $key = $clusterId . ($serverId === NULL ? '' : "_{$serverId}");
        if (empty(self::$_objList[$key])) {
            $obj = new self($clusterId, $serverId);
            $obj->init();
            self::$_objList[$key] = &$obj;
        }

        return self::$_objList[$key];
    }
    
    /**
     * 设置memcache连接方式为长连接
     */
    public static function persistent()
    {
        self::$_boolPersistent = TRUE;
    }
    
    /**
     * 从一个集群中随机选一台Server
     * @param int $clusterId
     */
    public static function & getSingleServerInstance($clusterId)
    {
        $key = $clusterId . '_single';
        if (empty(self::$_objList[$key])) {
            $cacheConfig = Config::get('cache_cluster');
            $serverIds = $cacheConfig[$clusterId];
            if (empty($serverIds)) {
                return FALSE;
            }
            $serverId = $serverIds[array_rand($serverIds)];
            self::$_objList[$key] = &self::getInstance($clusterId, $serverId);
        }
        
        return self::$_objList[$key];
    }
    
    /**
     * 获取缓存的静态方法
     *
     * @param string|array $key
     * @param integer $clusterId cluster id
     * @return mixed
     */
    public static function sGet($key, $clusterId = 'default')
    {
        $instance = self::getInstance($clusterId);
        return $instance->get($key);
    }
    

    public static function sInc($key,$val = 1,$clusterId = 'default')
    {
        $instance = self::getInstance($clusterId);
        return $instance->increment($key, $val);
    }
    

    /**
     * 从一个集群中的任一个Server中获取数据
     * 
     * @param sting $key
     * @param int $clusterId
     */
    public static function sGetFromSingleServer($key, $clusterId = 'default')
    {
        $instance = self::getSingleServerInstance($clusterId);
        return $instance->get($key);
    }
    
    /**
     * 设置缓存的静态方法
     *
     * @param string $key
     * @param mixed $val
     * @param integer $expires 过期时间（秒），0为永不过期
     * @param integer $clusterId cluster id
     * @return boolean
     */
    public static function sSet($key, $val, $expires = 0, $clusterId = 'default')
    {
        $instance = self::getInstance($clusterId);
        return $instance->set($key, $val, $expires);
    }
    
    /**
     * 将一个值缓存到集群的每一个Server
     * @param string $key
     * @param mixed $val
     * @param int $expires
     * @param int $clusterId
     */
    public static function sSetCluster($key, $val, $expires = 0, $clusterId = 'default')
    {
        $cacheConfig = Config::get('cache_cluster');
        $serverIds = $cacheConfig[$clusterId];
        
        foreach ($serverIds as $serverId) {
            $memcache = self::getInstance($clusterId, $serverId);
            $memcache->set($key, $val, $expires);
        }
    }
    
    /**
     * 追加缓存数据的静态方法
     * 
     * @param string $key
     * @param mixed $val
     * @param integer $clusterId
     * @return boolean
     */
    public static function sAppend($key, $val, $clusterId = 'default')
    {
        $instance = self::getInstance($clusterId);
        return $instance->append($key, $val);    
    }
    
    /**
     * 删除缓存数据的静态方法
     * 
     * @param string $key
     * @param integer $clusterId
     * @return boolean
     */
    public static function sDelete($key, $clusterId = 'default')
    {
        $instance = self::getInstance($clusterId);
        return $instance->delete($key);    
    }

    public static function flushAll($clusterId = 'default'){
        $instance = self::getInstance($clusterId);
        return $instance->flush();
    }
    
    /**
     * 删除集群中的每一个Server的某个值
     * 
     * @param string $key
     * @param int $clusterId
     */
    public static function sDeleteCluster($key, $clusterId = 'default')
    {
        $cacheConfig = Config::get('cache_cluster');
        $serverIds = $cacheConfig[$clusterId]; 
        foreach ($serverIds as $serverId) {
            $memcache = self::getInstance($clusterId, $serverId);
            $memcache->delete($key);
        }
    }
    
    /**
     * 增加缓存的静态方法
     * 
     * @param string $key
     * @param mixed $val
     * @param integer $expires
     * @param integer $clusterId
     * @return boolean
     */
    public static function sAdd($key, $val, $expires = 0, $clusterId = 'default')
    {
        $instance = self::getInstance($clusterId);
        return $instance->add($key, $val, $expires);
    }
    
    /**
     * 获取缓存数据
     *
     * @param string|array(string) $key, 可以传过一个数组来获取一组值
     * @return mixed 
     */
    public function get($key)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        if (is_array($key)) {
            $keyMap = array();
            $max = count($key);
            for ($i = 0; $i < $max; $i++) {
                $md5Key = md5($key[$i]);
                $keyMap[$md5Key] = $key[$i];
                $key[$i] = $md5Key;
            }
        } else {
            $key = md5($key);
        }
        
        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $res = $this->_memcache->get($key, self::FLAGS);
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');

        if (is_array($key)) {
            $nwRes = array();
            if ($res) {
	            foreach ($res as $md5Key => $val) {
	                $nwRes[$keyMap[$md5Key]] = $val;
	            }    
            }
            return $nwRes;
        }

        return $res;    
    }
    
    /**
     * 写入缓存
     *
     * @param string  $key  数据对应的键名
     * @param mixed   $val  数据
     * @param integer $expires 缓存的时间（秒），设置为0表示永不过期
     * @return boolean
     */
    public function set($key, $val, $expires = 0)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }
        if (is_numeric($val)) {
            $val = (string) $val;
        }

        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $ret = $this->_memcache->set(md5($key), $val, self::FLAGS, $expires);
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');
        
        if ($ret === FALSE) {
			//error_report(STAT_ER_MEMCACHE, 'MEMCACHE error:set fail,' . $this->_clusterId . ",$key");
			log_message('MEMCACHE error:set fail,' . $this->_clusterId . ",$key", LOG_ERR);
        }

        return $ret;
    }
    
    /**
     * 删除缓存
     *
     * @param string $key 数据的键名
     * @return boolean
     */
    public function delete($key)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }
        
        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $ret = $this->_memcache->delete(md5($key), 0);
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');

        return $ret;
    }
    
    /**
     * 增加item的值
     *
     * @param string $key
     * @param integer $val
     * @return boolean
     */
    public function increment($key, $val = 1)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }
        
        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $ret = $this->_memcache->increment(md5($key), $val);
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');

        return $ret;
    }

    /**
     * 写入缓存当且仅当$key对应缓存不存在的时候
     *
     * @param string $key
     * @param mixed  $val
     * @param integer $expires
     * @return boolean
     */
    public function add($key, $val, $expires = 0)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $ret = $this->_memcache->add(md5($key), $val, self::FLAGS, $expires);
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');

        return $ret;
    }
    
    
    /**
     * 在缓存数据尾部添加数据, 需要 memcache.so >= 3.0
     * 
     * @param $key
     * @param $val
     * @return boolean
     */
    public function append($key, $val)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }   

        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $ret = $this->_memcache->append(md5($key), $val);
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');

        return $ret;
    }
    
    /**
     * 刷新
     *
     * @return boolean
     */
    public function flush()
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        ETS::start(STAT_ET_MEMCACHE_CONNECT);
        $ret = $this->_memcache->flush();
        ETS::end(STAT_ET_MEMCACHE_CONNECT, $this->_clusterId.'('.$this->_serverId.')');

        return $ret;
    }
}
