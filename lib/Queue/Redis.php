<?php
require_once dirname(__FILE__) . '/Base.php';
class Queue_Redis implements IQueue
{
    public $name = 'doraemon';
    public $prefix = 'queue_';
    private $cache_prefix;

    public function __construct($name='', $prefix='')
    {
        $this->setKey($name , $prefix);
    }

    public function setKey($name , $prefix = '')
    {
        if($name != '') $this->name = $name;
        if($prefix != '') $this->prefix = $prefix;
        $this->cache_prefix = $this->prefix . $this->name . '_';
        return $this;
    }

    public function put($data)
    {
        $redis = Db_Redis::getInstance();
        if (! $redis) {
            return FALSE;
        }
        return $redis->lpush($this->cache_prefix, json_encode($data));
    }

    public function get()
    {
        $redis = Db_Redis::getInstance();
        if (! $redis) {
            return FALSE;
        }
        $task = $redis->rpop($this->cache_prefix);
        if ($task !== FALSE) {
            $task = @json_decode($task, TRUE);
        }
        return $task;
    }

    public function count()
    {
        $redis = Db_Redis::getInstance();
        if (! $redis) {
            return FALSE;
        }
        $len = $redis->lsize($this->cache_prefix);
        if ($len !== FALSE) {
            $len = intval($len);
        }
        return $len;
    }


    public function clear()
    {
        $redis = Db_Redis::getInstance();
        if (! $redis) {
            return FALSE;
        }
        $task = $redis->del($this->cache_prefix);
        return $task;
    }
}