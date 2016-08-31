<?php
class Util_Event
{
    private $_queue;
    private $_handles = array();
    public $mode;

    function __construct($mode, $key, $queue_type, $config = array())
    {
        $this->mode = $mode;
        if ($mode == 'async') {
            $config = array_merge(array(
                'name' => $key 
            ), $config);
            $this->_queue = new Queue_Base($config, $queue_type);
        }
    }

    /**
     * 引发一个事件
     * @param $event_type 事件类型
     * @return NULL
     */
    function raise()
    {
        $params = func_get_args();
        /**
         * 同步，直接在引发事件时处理
         */
        if ($this->mode == 'sync') {
            if (! isset($this->_handles[$params[0]]) or ! is_callable($this->_handles[$params[0]])) {
                trigger_error('Event Error:Event handle not found!', E_USER_ERROR);
            }
            return call_user_func_array($this->_handles[$params[0]], array_slice($params, 1));
        } /**
         * 异步，将事件压入队列
         */
        else {
            $this->_queue->put($params);
        }
    }

    /**
     * 增加对一种事件的监听
     * @param $event_type 事件类型
     * @param $call_back  发生时间后的回调程序
     * @return NULL
     */
    function addListener($event_type, $call_back)
    {
        $this->_handles[$event_type] = $call_back;
    }

    /*
    function run_server($time = 1, $log_file = null)
    {
        while ( true ) {
            $event = $this->_queue->get();
            if ($event and ! isset($event['HTTPSQS_GET_END'])) {
                if (! isset($this->_handles[$event[0]])) {
                    trigger_error('Event Error: empty event!', E_USER_ERROR);
                }
                $func = $this->_handles[$event[0]];
                if (! function_exists($func)) {
                    trigger_error('Event Error: event handle function not exists!', E_USER_ERROR);
                } else {
                    $parmas = array_slice($event, 1);
                    call_user_func_array($func, $parmas);
                    trigger_error('Event Info: process success!event type ' . $func . ',params(' . implode(',', $parmas) . ')', E_USER_ERROR);
                }
            } else {
                usleep($time * 1000);
                 //echo 'sleep',NL;
            }
        }
    }
*/
    function run_script($time)
    {
        while ( $event = $this->_queue->get() ) {
            if (count($event) < 1) {
                continue;
            }
            if (! isset($this->_handles[$event[0]])) {
                trigger_error('Event Error: empty event!', E_USER_ERROR);
            }
            $func = $this->_handles[$event[0]];
            if (! is_callable($func)) {
                trigger_error('Event Error: event handle function not exists!', E_USER_ERROR);
            } else {
                $parmas = array_slice($event, 1);
                call_user_func_array($func, $parmas);
               // log_message('Event Info: process success!event type ' . json_encode($func) . ',params(' . json_encode($parmas) . ')');
            }
        }
    }

    /**
     * 设置监听列表
     * @param $listens
     * @return unknown_type
     */
    function set_listens($listens)
    {
        $this->_handles = array_merge($this->_handles, $listens);
    }

    public static function getInstance($mod, $key, $queue_type, $config = array())
    {
        static $objArr = array();
        ksort($config);
        $index = "{$mod}@{$key}@{$queue_type}@" . json_encode($config);
        if (! is_object(@$objArr[$index])) {
            $obj = new self($mod, $key, $queue_type, $config);
            $objArr[$index] = $obj;
        }
        return $objArr[$index];
    }
}
