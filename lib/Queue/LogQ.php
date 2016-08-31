<?php
require_once dirname(__FILE__) . '/Redis.php';
class Queue_LogQ 
{
    const LOGQ_KEY = 'weblogs_';
    private $queueRedis = null;

    public function __construct()
    {
        $this->queueRedis = new Queue_Redis();
    }

    public static function getInstance()
    {
        static $logQ = null;
        if ($logQ == null)
        {
            $logQ = new Queue_LogQ();
        }
        return $logQ;
    }

    public function setKey($name = '' , $prefix = '')
    {
        if (empty($name))
        {
            $name = date("YmdH" , time());
        }
        if (empty($prefix))
        {
            $prefix = self::LOGQ_KEY;
        }
        $this->queueRedis->setKey($name , $prefix);
        return $this;
    }

    public function count()
    {
         return $this->queueRedis->count();
    }

    public  function put($data)
    {
        $this->queueRedis->put($data);
    }

    public  function get($limit = 1)
    {
        $count = $this->queueRedis->count();
        $limit = min($count , $limit);
        $data = array();
        $i = 0;
        while ($i < $limit) {
            $data[] = $this->queueRedis->get();
            $i++;
        }
        return $data;
    }

    public function del()
    {
        $this->queueRedis->clear();
    }

    public function __destruct()
    {
        $this->queueRedis = null;
    }
}