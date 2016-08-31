<?php
/**
 * Redis 二次封装，方便支持 缓存的设置
 * @author zhangzhan
 */
class Cache_Redis
{
    
    protected $_redises = array();//保存链接好的redises对象
    protected $_redisIndex = array();
    
    protected $_clusterId;
    
    protected $_selectFun = NULL;
    
    protected $_isMulti = FALSE;
    
    protected $_multiRedis = NULL;//保存在多个指令发送时选择的redis操作对象，保证在一个redis中执行
    
    public function __construct($clusterId)
    {
        $this->_clusterId = $clusterId;
        $pyHosts = Config::get('redis_physical');
        $redisConfig = Config::get('redis_cluster');
        
        if (empty($redisConfig[$clusterId])) {
            return;
        }
        
        foreach ($redisConfig[$clusterId] as $rh) {
            $r = new Redis();
            $prh = $pyHosts[$rh];
            if($r->pconnect($prh['host'], $prh['port'])) {
                $this->_redises[] = $r;
            }
        }
        
        $this->_redisIndex = array_keys($this->_redises);
    }
    
    public function __destruct() {
        if (empty($this->_redises)) {
            return;
        }
        
        foreach($this->_redises as $r) {
            $r->close();
        }
    }
    
    
    public function select($db)
    {
        if (empty($this->_redises)) {
            return;
        }
        
        $result = TRUE;
        foreach($this->_redises as $r) {
            $result &= $r->select($db);
        }
        
        return $result;
    }
    
    public function hmGet($key, $memberKeys)
    {
        if (empty($this->_redises)) {
            return FALSE;
        }
        
        $r = $this->selectRedis($key);
        return $r->hMget($key, $memberKeys);
    }
    
    public function hmSet($key, $members)
    {
        if (empty($this->_redises)) {
            return FALSE;
        }
        
        $r = $this->selectRedis($key);
        return $r->hMset($key, $members);
    }
    
    public function multi($mainkey, $option = Redis::MULTI) {
        if ($this->_isMulti) {
            return;
        }
        $this->_multiRedis = $this->selectRedis($mainkey);
        $this->_isMulti = TRUE;
        
        $this->_multiRedis->multi($option);
    }
    
    public function exec() {
        if (!$this->_isMulti) {
            return;
        }
        $result = $this->_multiRedis->exec();
        $this->_isMulti = FALSE;
        $this->_multiRedis = NULL;
        return $result;
    }
    
    public function flushAll()
    {
        if (empty($this->_redises)) {
            return;
        }
        
        $result = TRUE;
        foreach($this->_redises as $r) {
            $result &= $r->flushAll();
        }
        
        return $result;
    }
    
    /**
     * 根据给定的key返回一个要操作的redis链接对象 
     */
    protected function selectRedis($key)
    {
        if ($this->_isMulti) {
            return $this->_multiRedis;
        }
        $ip = $this->defaultRedisSelector($key, $this->_redisIndex);
        return @$this->_redises[$ip];
    }
    
    protected function defaultRedisSelector($key, $redisIndex)
    {
        $redisNum = count($redisIndex);
        
        if ($redisNum === 0) {
            return 0;
        }
        
        $hash = crc32 ($key);
        
        $index = abs($hash % $redisNum);
        
        if (isset($redisIndex[$index])) {
            return $redisIndex[$index];
        }
    }
    
    
    public static function getInstance($clusterId, $db = 0)
    {
        static $objArr = array();
        $key = $clusterId . $db;
        if (!is_object(@$objArr[$key])) {
            $obj = new Cache_Redis($clusterId);
            if ($obj->select($db)) {
                $objArr[$key] = $obj;
            }
            else {
                $objArr[$key] = FALSE;
            }
        }
        
        return $objArr[$key];
    }
    
}