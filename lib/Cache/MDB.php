<?php
/**
 * Mongodb操作封装
 */
class Cache_MDB
{
    protected static $_objList;
    private $_mongo;
    private $_clusterId;
    
    private function __construct($clusterId)
    {
        $this->_clusterId = $clusterId;
    }
    
    /**
     * 获取一个MongoDb的实例
     *
     * @return object
     */
    public static function & getInstance($clusterId)
    {
        $key = $clusterId;
        if (empty(self::$_objList[$key])) {
            $obj = new self($clusterId);
            $obj->init();
            self::$_objList[$key] = &$obj;
        }

        return self::$_objList[$key];
    }

    private function init()
    {
        $clusterId = $this->_clusterId;
        if(empty($clusterId)) {
            return FALSE;
        }
        $mongoConfig = Config::get('mongo_config');
        if (empty($mongoConfig) || ! isset($mongoConfig[$clusterId]) || empty($mongoConfig[$clusterId])) {
            return FALSE;
        }
        $mongoHosts = $mongoConfig[$clusterId];
        $phyHosts = Config::get('mongo_physical');
        if(empty($phyHosts) || ! isset($phyHosts[$mongoHosts[0]]) || empty($phyHosts[$mongoHosts[0]])) {
            return FALSE;
        }
        $mongReplSet = $phyHosts[$mongoHosts[0]];
        if(! isset($mongReplSet['seedList']) || ! isset($mongReplSet['replSetName']) || empty($mongReplSet['seedList']) || empty($mongReplSet['replSetName'])) {
            return FALSE;
        }
        $options = array('replicaSet' => $mongReplSet['replSetName'], 'readPreference' => MongoClient::RP_NEAREST);
        $connectStr = "mongodb://" . implode(',', $mongReplSet['seedList']) . "/?" . http_build_query($options);
        try {
            $this->_mongo = new MongoClient($connectStr);
        } catch (MongoConnectionException $e) {
            log_message(json_encode($e->getMessage()), LOG_ERR);
            return FALSE;
        }
        return TRUE;
    }
    
    public static function mInsert($clusterId, $db, $col, $doc, $opt = array())
    {
        ETS::start(STAT_ET_MONGO_CONNECT);
        $instance = self::getInstance($clusterId);
        $collection = $instance->_selectCollection($db, $col);
        $res = FALSE;
        if(FALSE !== $collection) {
            try {
                $res = $collection->insert($doc, $opt);
            } catch(MongoException $e) {
                log_message(json_encode($e->getMessage()), LOG_ERR);
                ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
                return FALSE;
            }
        }
        ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
        return $res;
    }
    
    public static function mRemove($clusterId, $db, $col, $cr, $opt = array())
    {
        ETS::start(STAT_ET_MONGO_CONNECT);
        $instance = self::getInstance($clusterId);
        $collection = $instance->_selectCollection($db, $col);
        $res = FALSE;
        if(FALSE !== $collection) {
            try {
                $res = $collection->remove($cr, $opt);
            } catch(MongoException $e) {
                log_message(json_encode($e->getMessage()), LOG_ERR);
                ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
                return FALSE;
            }
        }
        ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
        return $res;
    }
    
    public static function mUpdate($clusterId, $db, $col, $cr, $up, $opt = array())
    {
        ETS::start(STAT_ET_MONGO_CONNECT);
        $instance = self::getInstance($clusterId);
        $collection = $instance->_selectCollection($db, $col);
        $res = FALSE;
        if(FALSE !== $collection) {
            try {
                $res = $collection->update($cr, $up, $opt);
            } catch(MongoException $e) {
                log_message(json_encode($e->getMessage()), LOG_ERR);
                ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
                return FALSE;
            }
        }
        ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
        return $res;
    }
    
    public static function mFind($clusterId, $db, $col, $cr, $filed = array())
    {
        ETS::start(STAT_ET_MONGO_CONNECT);
        $instance = self::getInstance($clusterId);
        $collection = $instance->_selectCollection($db, $col);
        $cursor = FALSE;
        if(FALSE !== $collection) {
            try {
                $cursor = $collection->find($cr, $filed);
            } catch(MongoException $e) {
                log_message(json_encode($e->getMessage()), LOG_ERR);
                ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
                return FALSE;
            }
        }
        ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
        return $cursor;
    }
    
    public static function mOtherMethod($clusterId, $db, $col, $method, $ct, $opt = array())
    {
        ETS::start(STAT_ET_MONGO_CONNECT);
        $instance = self::getInstance($clusterId);
        $collection = $instance->_selectCollection($db, $col);
        $res = FALSE;
        if(FALSE !== $collection) {
            try {
                $res = $collection->{$method}($ct, $opt);
            } catch(MongoException $e) {
                log_message(json_encode($e->getMessage()), LOG_ERR);
                ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
                return FALSE;
            }
        }
        ETS::end(STAT_ET_MONGO_CONNECT, $clusterId);
        return $res;
    }
    
    protected function _selectCollection($db, $col, $type = NULL)
    {
        if (empty($this->_mongo)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }
        $collection = FALSE;
        try {
            $collection = $this->_mongo->{$db}->{$col};
        } catch (Exception $e) {
            log_message(json_encode($e->getMessage()), LOG_ERR);
            return FALSE;
        }
        return $collection;
    }
}