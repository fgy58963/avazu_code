<?php
abstract class Model_Cacheable extends Db_Model
{
	public $DISABLE_CACHE_TRIGGER = FALSE;
	
    protected static $CACHE = array();
    
    protected $CACHE_TIME = 7200;
    protected $MEMCACHE_CLUSTER_ID = 'default';
    protected $COLUMN_ID = 'id';
    protected $ITEM_NAME = '';
	
    function __construct($table = NULL, $clusterId = NULL, $objectId = NULL)
    {
        parent::__construct($table, $clusterId, $objectId);
		//$class = get_class($this);
		//$name = strtolower(preg_replace('@^Model_@', '', $class));
        $name = strtolower($table);
		$this->ITEM_NAME = $clusterId . '_' . $name;
    }
	
	public function getItemsByConds($conds, $cacheTime = 0,$refresh = FALSE)
	{
		return $this->select($conds, array(), $cacheTime,$refresh);
	}
	
    /*
        $saveInLocal 暂时不用
    */
	public function select($where = array(), $attrs = array(), $cacheTime = 0,$refresh = FALSE, $useLocalCache = FALSE)
	{
		$useCache = $cacheTime > 0;
		$cacheKey = $this->getQueryCacheKey($where, $attrs);
		if (!$refresh) {
            if ($useCache) {
                $items = Cache_Memcached::sGet($cacheKey, $this->MEMCACHE_CLUSTER_ID);
                log_message("from cache,cacheKey:{$cacheKey},where: ".json_encode($where),LOG_DEBUG);
                if ($items) {
                    return $items;
                }
            }
        }
		
		$items = parent::select($where, $attrs);	
		
		if ($useCache && $items) {
			Cache_Memcached::sSet(
				$cacheKey, $items, $cacheTime,
				$this->MEMCACHE_CLUSTER_ID
			);
		}
		
		return $items;
	}

    public function selectOne($where = array(), $attrs = array(), $cacheTime = 0,$refresh = FALSE)
    {
        $attrs['limit'] = 1;
        $attrs['offset'] = 0;


        $items =  $this->select($where , $attrs, $cacheTime ,$refresh);
        if (empty($items)) {
            return NULL;
        }
        return $items[0];

    }

    public function execute($sql, $cacheTime = 0,$refresh = FALSE)
    {
        $useCache = $cacheTime > 0;
        $cacheKey = md5($sql);
        if (!$refresh) {
            if ($useCache) {
                $items = Cache_Memcached::sGet($cacheKey, $this->MEMCACHE_CLUSTER_ID);
                if ($items) {
                    return $items;
                }
            }
        }

        $items = parent::execute($sql);

        if ($useCache && $items) {
            Cache_Memcached::sSet(
                $cacheKey, $items, $cacheTime,
                $this->MEMCACHE_CLUSTER_ID
            );
        }

        return $items;
    }
	
	protected function getQueryCacheKey($where, $attrs = array())
	{
        if(is_array($where)){
		  ksort($where);
        }
		$key = 'QSQL_'.$this->getDatabaseName() . '.' . $this->_table.'_'
			 . $this->_sqlHelper->where($where, $attrs);

        log_message("key: {$key} ",LOG_DEBUG);
		return md5($key);
	}

    public function get($ids, $useCache = TRUE, $expire = 0, $pageInCache = true) 
    {
        return $this->getItems($ids, $useCache, $expire, $pageInCache);
    }
	
	protected static function _get($class, $ids, $useCache = TRUE, $expire = 0, $pageInCache = TRUE)
	{
		$instance = Factory::getInstance($class);
		return $instance->getItems($ids, $useCache, $expire, $pageInCache);
	}
	
	//数据更新触发器
	protected function afterUpdate($conds, $data)
	{
		if ($this->DISABLE_CACHE_TRIGGER) {
            parent::afterUpdate($conds, $data);
			return;
		}
		
		if (isset($conds[$this->COLUMN_ID])) {
			$this->clearItemCache($conds[$this->COLUMN_ID]);
		} else {
		    $this->clearItemByConds($conds);
		}

        parent::afterUpdate($conds, $data);
	}
	
	protected function afterInsertReplace($ins, $replace, $ret)
	{
		if ($this->DISABLE_CACHE_TRIGGER) {
            parent::afterInsertReplace($ins, $replace);
			return;
		}
		
		if (isset($ins[$this->COLUMN_ID])) {
			$this->clearItemCache($ins[$this->COLUMN_ID]);
		} else {
		    $this->clearItemByConds($ins);
		}

        parent::afterInsertReplace($ins, $replace, $ret);
	}
	
	//数据删除触发器
	protected function afterDelete($conds)
	{
		if (isset($conds[$this->COLUMN_ID])) {
			$this->clearItemCache($conds[$this->COLUMN_ID]);
		} else {
		    $this->clearItemByConds($conds);
		}

        parent::afterDelete($conds);
	}
	
	protected function clearItemByConds($conds) {
	    $attrs = array(
	        'select' => $this->COLUMN_ID
	    );
	    
	    $results = $this->select($conds, $attrs);
	    if (!is_array($results)) {
	        //XXX 错误处理
            log_message("not array results",LOG_DEBUG);
	        return;
	    }
	    
	    $ids = array_get_column($results, $this->COLUMN_ID);
	    if (!empty($ids)) {
	        $this->clearItemCache($ids);
	    }else{
            log_message("empty ids ",LOG_DEBUG);

        }
	}
	
    public function getItems($ids, $useCache = TRUE, $expire = 0, $pageInCache = true)
    {
        $ret = array();
        if (empty($ids)) {
            return $ret;
        }
        
        $returnArray = TRUE;
        
        if (!is_array($ids)) {
            $ids = array($ids);
            $returnArray = FALSE;
        }
        
        $ids = array_unique($ids);
        if ($useCache) {
            $idsUncached = NULL;
            $itemsCached = $this->getItemsCache($ids, $idsUncached, $pageInCache);
        } else {
            $idsUncached = $ids;
        }
        
        $itemsUncached = NULL;
        
        if (!empty($idsUncached)) {
            log_message('ids:' . json_encode($idsUncached), LOG_DEBUG);
            $conds = array($this->COLUMN_ID => $idsUncached);
            $otherConds = array();
            $itemsUncached = $this->select($conds, 0, 0, NULL, $otherConds);
            if ($itemsUncached) {
                log_message(
                    $this->ITEM_NAME.":Get items(".count($itemsUncached).") from db",
                    LOG_DEBUG
                );
                array_change_key($itemsUncached, $this->COLUMN_ID);
	            if ($useCache) {
	                $this->setItemsCache($itemsUncached, $expire);
	            }
            }
        }
        
        if ($useCache) {
            $ret = $itemsCached + ($itemsUncached ? $itemsUncached : array());
        } else {
            $ret = $itemsUncached;
        }
        
        if (!$returnArray) {
            if(!isset($ids[0])){
                $ret = NULL; 
            }else{
                $ret = isset($ret[$ids[0]]) ? $ret[$ids[0]] : NULL;
            }
        }
        
        return $ret;
    }
    
    protected function getItemsCache($ids, &$idsUncached = NULL, $pageInCache = true)
    {
        $itemsInPageCache = array();
		
        if ($pageInCache && !empty(self::$CACHE[$this->ITEM_NAME])) {
            $idsUncached = array();
            foreach ($ids as $id) {
				$cackeKey = $this->getItemCacheKey($id);
                if (!empty(self::$CACHE[$this->ITEM_NAME][$cackeKey])) {
                    $itemsInPageCache[$id] = self::$CACHE[$this->ITEM_NAME][$cackeKey];
                } else {
                    $idsUncached[] = $id;
                }
            }
			if ($itemsInPageCache) {
				log_message($this->ITEM_NAME.':get items('.count($itemsInPageCache).') from page cache.', LOG_DEBUG);
			}
            if (empty($idsUncached)) {
                return $itemsInPageCache;
            }
            $ids = $idsUncached;
        }
		
        $keys = $this->getItemCacheKey($ids);
        $items = Cache_Memcached::sGet($keys, $this->MEMCACHE_CLUSTER_ID);
        if (empty($items) && !is_array($items)) {
            $items = array();
        }
        $idsUncached = array();
        foreach ($keys as $key) {
            if (empty($items[$key])) {
                log_message('id:' . $key, LOG_DEBUG);
                $idsUncached[] = $this->getIdFromItemCacheKey($key);
            } else if ($pageInCache) {
                self::$CACHE[$this->ITEM_NAME][$key] = $items[$key];
            }
        }
        
        array_change_key($items, $this->COLUMN_ID);
        
        if ($itemsInPageCache) {
            $items = $items + $itemsInPageCache;
        }
        $items_count = count($items);
        $append_log_str ='';
        if($items_count < 5){
            $append_log_str = 'ids:'.implode(',',$keys).',keys:'.implode(',',$keys);
        }
        log_message(
            $this->ITEM_NAME.":Get items(count : {$items_count}) , {$append_log_str} from cache",
            LOG_DEBUG
        );
        return $items;    
    }



    /**
     * 根据条件取一条记录，使用cache
     * @param  array $conds exp: array('code' => 'aaa','status' =>'111')
     * @return array|boolen       [description]
     */
    public function getItemByConds ($conds,$cacheKey,$useCache=true)
    {

        if(empty($conds)){
            return false;
        }

        if(!$useCache){
            log_message("from db, conds:".json_encode($conds),LOG_DEBUG);
            $item = $this->selectOne($conds);
            return $item;

        }

        $key = replaceMacros($cacheKey, $conds);
        $items = Cache_Memcached::sGet($key);
        if (!empty($items))
        {
            log_message("from cache, key:".$key,LOG_DEBUG);
            return $items;
        }
        $item = $this->selectOne($conds);
        if (!empty($item))
        {
            log_message("save cache, key:".$key,LOG_DEBUG);
            Cache_Memcached::sSet($key, $item);
        }
        return $item;

    }


    // /**
    //  * 根据条件取多条记录，使用cache
    //  * @param  array $conds exp: array('code' => 'aaa','status' =>'111')
    //  * @return array|boolen       [description]
    //  */
    // public function getItemsByConds ($conds,$cacheKey,$useCache=true)
    // {

    //     if(empty($conds)){
    //         return false;
    //     }

    //     if(!$useCache){
    //         log_message("from db, conds:".json_encode($conds),LOG_DEBUG);
    //         $items = $this->select($conds, 0, 0, NULL, $otherConds);
    //         return $item;

    //     }

    //     $key = replaceMacros($cacheKey, $conds);
    //     $items = Cache_Memcached::sGet($key);
    //     if (!empty($items))
    //     {
    //         log_message("from cache, key:".$key,LOG_DEBUG);
    //         return $items;
    //     }

    //     $otherConds = array();
    //     $items = $this->select($conds, 0, 0, NULL, $otherConds);
    //     if (!empty($items))
    //     {
    //         log_message("save cache, key:".$key,LOG_DEBUG);
    //         Cache_Memcached::sSet($key, $items);
    //     }
    //     return $items;

    // }

    protected function addItemCache($id, $item, $expire = 0)
    {
        $cacheKey = $this->getItemCacheKey($id);
        if ($pageInCache) {
            self::$CACHE[$this->ITEM_NAME][$cacheKey] = $item;
        }
        Cache_Memcached::sAdd(
            $cacheKey, $item,
            ($expire == 0 ? $this->CACHE_TIME : $expire),
            $this->MEMCACHE_CLUSTER_ID
        );
    }
    
    protected function setItemsCache($items, $expire = 0, $pageInCache = true)
    {
        $expire = ($expire == 0 ? $this->CACHE_TIME : $expire);
        foreach ($items as $id => $item) 
        {   $cacheKey = $this->getItemCacheKey($id);
            if ($pageInCache) {
                self::$CACHE[$this->ITEM_NAME][$cacheKey] = $item;
            }
            Cache_Memcached::sSet(
                $cacheKey, $item,
                $expire,
                $this->MEMCACHE_CLUSTER_ID
            );
        }
    }
    
    public function clearItemCache($ids)
    {
        if (! is_array($ids)) {
            $ids = array($ids);
        }
        
        foreach ($ids as $id) {
            $cacheKey = $this->getItemCacheKey($id);
            if (isset(self::$CACHE[$this->ITEM_NAME][$cacheKey])) {
                unset(self::$CACHE[$this->ITEM_NAME][$cacheKey]);
            }
            Cache_Memcached::sDelete($cacheKey, $this->MEMCACHE_CLUSTER_ID);
            // DEBUG
            log_message("clearItemCache ,id:{$id}, cacheKey:{$cacheKey}",LOG_DEBUG);
        }
    }

    protected function clearCache($keys, $key_format, $conds)
    {
        $data = $this->select($conds, array('select' => $keys));
        if ($data) 
        {
            foreach ($data as $key => $item) 
            {
                $key_str = replaceMacros($key_format, $item);
                if(Cache_Memcached::sDelete($key_str) === FALSE)
                {
                    log_message('delete mem key error, key:' . $key_str . ' key format:' . $key_format, LOG_ERR);
                }
            }
        }
    }

    protected function cleanCacheByKey($str, $arr){
        $key = replaceMacros($str, $arr);

        if(Cache_Memcached::sDelete($key) === FALSE)
        {
            log_message('delete mem key error, key:' . $key . ' key format:' . json_encode($arr), LOG_WARNING);
        }
    }
    
    private function getItemCacheKey($id)
    {
        if (is_array($id)) {
            $ret = array();
            foreach ($id as $val) {
                $ret[] = $this->getItemCacheKey($val);
            }
            return $ret;
        }
		if (empty($this->ITEM_NAME)) {
			throw new Exception($this->ITEM_NAME.":The cache item name is empty.");
		}
        return $this->ITEM_NAME.'_'.$id;
    }
    
    private function getIdFromItemCacheKey($key)
    {
		$len = strlen($this->ITEM_NAME);
        $text = substr($key, $len + 1);
        $tmp = intval($text);
        return strval($tmp) != $text ? $text : $tmp;
    }
}
