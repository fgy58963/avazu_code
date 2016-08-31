<?php
require_once (PATH_LIB . DS . 'Queue/LogQ.php');
class SP_G {
    public static $events = array ();
}
function getGUID($raw) {
    if (strlen ( $raw ) != 32) {
        return false;
    }
    return '{' . substr ( $raw, 0, 8 ) . '-' . substr ( $raw, 8, 4 ) . '-' . substr ( $raw, 12, 4 ) . '-' . substr ( $raw, 16, 4 ) . '-' . substr ( $raw, 20 ) . '}';
}

function getSessID($str){
    return md5($str);
}

function md5_16($str){
    return substr(md5($str),8,16);
}

/**
 * log
 */
function log_message($msg, $level = LOG_INFO, $group = '') {
    //static $errorCount = 0;
    //static $errorTime = null;
    //if (empty($errorTime)) $errorTime = time();
    $args = func_get_args ();
    if (trigger_event ( 'on_log_message', $args )) {
        return;
    }
    if (! (is_int ( $level ) && $level <= LOG_DEBUG && $level >= LOG_EMERG)) {
        return;
    }

    //高优先级
    /*
          if(isset($_GET['is_debug']) && 1 == $_GET['is_debug']){
                if (!defined('PRIORITY_LOG_LEVEL')) {
                   define('PRIORITY_LOG_LEVEL', LOG_DEBUG);
                }            
          }           
     */
    if (defined ( 'PRIORITY_LOG_LEVEL' )){
        $logLevel = PRIORITY_LOG_LEVEL;
    }
    else if (defined ( 'LOG_LEVEL' )) {
            $logLevel = LOG_LEVEL;
    }
    else {
            $logLevel = LOG_ERR;
    }
    $logTypes = array (
            LOG_DEBUG => 'DEBUG', //7
            LOG_INFO => 'INFO',     //6
            LOG_NOTICE => 'NOTICE',
            LOG_WARNING => 'WARNING',
            LOG_ERR => 'ERR',
            LOG_CRIT => 'CRIT',
            LOG_ALERT => 'ALERT',
            LOG_EMERG => 'EMERG'  //0
    );


    $logType = $logTypes [$level];

     //取一次会话的标记
    $session_key = Util_Tool::getInstance()->getLogSessionKey();
    //附加信息
    $addition_msg = '['.$session_key.'] '.date('Y-m-d H:i:s') . " [{$logType}] ";
    if(!empty($group)){
        $addition_msg.="  [{$group}]";
    }

    //取调用者的方法名
    $backtrace = array();
    if(ENV ==  'LOCAL' || ENV == 'DEVELOPMENT' || LOG_DEBUG == $logLevel ){
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS & DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $msg=sprintf('[%s:%s] ', @get_called_class(), ''). $msg; 
    }    
    if(!empty($backtrace) && isset($backtrace[1])){
        $backtrace_caller = $backtrace[1];
        $call_class_name = isset($backtrace_caller['class']) ? $backtrace_caller['class'] : '';
        // $call_line = isset($backtrace_caller['line']) ? $backtrace_caller['line'] : '';
        $call_function_name = isset($backtrace_caller['function']) ? $backtrace_caller['function'] : '';
         $addition_msg.=sprintf('[%s:%s] ', $call_class_name, $call_function_name); 
    }
    

    $logdir = PATH_LOG;
    if (defined ( 'APP_PATH_LOG' )) {
        $logdir = APP_PATH_LOG;
    }


    //udp log 记录所有日志
    $udplog_config = Config::get('udplog');
    if (!empty($udplog_config) && !empty($udplog_config[0]))
    {
        $logdir_pathinfo = pathinfo($logdir);
        $udpMsg =  '['.date ( 'Y-m-d H:i:s' ).']['.$logType.']['.$logdir_pathinfo['basename'].'] '.$msg;

        // var_dump($udpMsg);
        //注意 ,udp log 格式必须这样
        UdpLog_LogMsg::getInstance()->sendMsg($udpMsg);
    }
    //END UDPLOG


    $msg = $addition_msg." {$msg}\n";


    //线上文件日志不记录 debug级别日志， 当前日志级别 <= 设定最级别
    if ($level <= $logLevel) {
        $fn = date ( 'Ymd' ) . ".log";

        $fileName = $logdir . DS . $fn;

        if (! is_dir ( $logdir )) {
            mkdir ( $logdir, 0755, TRUE );
        }
        $fileName = $logdir . DS . $fn;
        if (! file_exists ( $fileName )) {
            error_log ( $msg, 3, $fileName );
            @chmod ( $fileName, 0755 );
        } else {
            error_log ( $msg, 3, $fileName );
        }    

        if (defined ( 'LOG_STDOUT' )) {
            echo $msg;
        }
     }


    if (defined ( 'LOG_SCRIBE_LEVEL' )) {
        $logScribeLevel = LOG_SCRIBE_LEVEL;
    } else {
        $logScribeLevel = LOG_ERR;
    }
    
}

function getExt($url){   
    $arr = parse_url($url);     
    $file = $arr['host'];   
    $ext = substr($file,strpos($file,".")+1);   
    return $ext;
}

function trimAll($str)//删除空格
{
    $qian=array(" ","　","\t","\n","\r");$hou=array("","","","","");
    return str_replace($qian,$hou,$str);    
}

/**
 * 报警
 */
if (! function_exists ( 'error_report' )) {
    function error_report($errno, $msg, $smsMsg = '') {
        log_message ( 'error_report:' . $msg, LOG_ERR );
        // send mail or sms?
    }
}
if (! function_exists ( 'class_alias' )) {
    function class_alias($original, $alias) {
        if (! class_exists ( $alias )) {
            eval ( 'abstract class ' . $alias . ' extends ' . $original . ' {}' );
        }
    }
}
function v_($arr, $key, $default) {
    return (! empty ( $arr ) && isset ( $arr [$key] )) ? $arr [$key] : $default;
}
function h_($str) {
    return nl2br ( htmlspecialchars ( $str ) );
}

// 将数据库的时间戳直接返回变为时间戳
function ts_($timeStr) {
    if (empty ( $timeStr )) {
        return 0;
    } else {
        return strtotime ( $timeStr );
    }
}
function form_hash($name, $html = TRUE) {
    $uid = isset ( $GLOBALS ['myuid'] ) ? $GLOBALS ['myuid'] : 0;
    $hash = sha1 ( date ( 'Y-m-d' ) . $name . '-ITISASECRETKEY!' . "-$uid" );
    if ($html) {
        return '<input type="hidden" name="' . FORM_HASH_NAME . '" value="' . $name . '"/>' . "\n" . '<input type="hidden" name="' . FORM_HASH . '" value="' . $hash . '"/>';
    }
    return $hash;
}
function check_form_hash($name) {
    $hash = isset ( $_GET [FORM_HASH] ) ? $_GET [FORM_HASH] : (isset ( $_POST [FORM_HASH] ) ? $_POST [FORM_HASH] : '');
    $genHash = form_hash ( $name, FALSE );
    return $hash === $genHash;
}
function set_default_cookie($name, $value, $expires = 0) {
    if (is_array ( $value )) {
        $value = json_encode ( $value );
    }
    $domain = $_SERVER ['HTTP_HOST'];
    setcookie ( $name, $value, $expires, '/', $domain );
    $_COOKIE [$name] = $value;
}
function unset_default_cookie($name) {
    $domain = $_SERVER ['HTTP_HOST'];
    setcookie ( $name, '', time () - 3600 * 24 * 365, '/', $domain );
    unset ( $_COOKIE [$name] );
}

/**
 * Ajax方式返回数据到客户端
 * 
 * @access protected
 * @param mixed $data
 *            要返回的数据
 * @param String $type
 *            AJAX返回数据格式
 * @return void
 */
function ajax_return($data, $type = '') {
    if (empty ( $type ))
        $type = 'JSON';
    switch (strtoupper ( $type )) {
        case 'JSON' :
            // 返回JSON数据格式到客户端 包含状态信息
//            header ( 'Content-Type:application/json; charset=utf-8' );
            // exit ( json_encode ( $data ) );
            echo json_encode ( $data );
            die();
        case 'XML' :
            // 返回xml格式数据
            header ( 'Content-Type:text/xml; charset=utf-8' );
            // exit ( xml_encode ( $data ) );
            echo xml_encode ( $data );
            die();
        case 'JSONP' :
            // 返回JSON数据格式到客户端 包含状态信息
            header ( 'Content-Type:application/json; charset=utf-8' );
            $handler = isset ( $_GET ['callback'] ) ? $_GET ['callback'] : 'jsonpReturn';
            // exit ( $handler . '(' . json_encode ( $data ) . ');' );
            echo $handler . '(' . json_encode ( $data ) . ');';
            die();
        case 'EVAL' :
            // 返回可执行的js脚本
            header ( 'Content-Type:text/html; charset=utf-8' );
            // exit ( $data );
            echo $data;
            die();
    }
}

function http_api_succ($data = array()) {
    ajax_return ( array (
            'code' => RET_SUCC,
            'msg' => '',
            'data' => $data 
    ) );
}
function http_api_fail($errorCode = RET_ERROR, $errorMsg = '') {
    ajax_return ( array (
    'code' => $errorCode,
    'msg' => $errorMsg,
    'data' => null
    ) );
}
/**
 * 执行时间统计
 * Execution time statictis
 */
class ETS {
    private static $starts = array ();
    private static $warnTimes = array (
            STAT_ET_DB_CONNECT => 0.1,
            STAT_ET_DB_QUERY => 0.1,
            STAT_ET_MEMCACHE_CONNECT => 0.05,
            STAT_ET_REDIS => 0.05,
            STAT_ET_MONGO_CONNECT => 0.1 
    );
    private static $names = array (
            STAT_ET_DB_CONNECT => 'DB_Connect',
            STAT_ET_DB_QUERY => 'DB_Query',
            STAT_ET_MEMCACHE_CONNECT => 'MEMCACHE_Connect',
            STAT_ET_REDIS => 'REDIS_Query',
            STAT_ET_MONGO_CONNECT => 'MONGO_Query' 
    );
    public static function start($name) {
        self::$starts [$name] = microtime ( TRUE );
    }
    public static function end($name, $msg = '') {
        if (empty ( self::$starts [$name] )) {
            return FALSE;
        }
        $start = self::$starts [$name];
        $end = microtime ( TRUE );
        $executeTime = $end - $start;
        if (isset ( self::$warnTimes [$name] )) {
            if ($executeTime > self::$warnTimes [$name]) {
                $log = 'ET:' . self::$names [$name] . ':' . $executeTime . ':' . $msg;
                log_message ( $log, LOG_WARNING );
            }
        }
        return $executeTime;
    }
}

/**
 * 事件驱动函数
 */
/**
 * 触发事件
 * 
 * @name string 事件名
 * @param
 *            s array 事件参数
 * @return void
 */
function trigger_event($name, $params) {
    static $eventStack = array ();
    /*
     * if ($name != 'on_log_message' && $name != 'on_autoload') { log_message("trigger event $name start", LOG_DEBUG); }
     */
    $ret = FALSE;
    $handlers = empty ( SP_G::$events [$name] ) ? array () : SP_G::$events [$name];
    $defaultHandler = "event_handler_{$name}";
    if (is_callable ( $defaultHandler )) {
        $handlers [] = array (
                'function' => $defaultHandler 
        );
    }
    if (empty ( $handlers )) {
        return $ret;
    }
    if (! is_array ( $params )) {
        $params = array (
                $params 
        );
    }
    if (isset ( $GLOBALS ['__EVENT_NAME'] )) {
        $eventStack [] = $GLOBALS ['__EVENT_NAME'];
    }
    $GLOBALS ['__EVENT_NAME'] = $name;
    foreach ( $handlers as $options ) {
        if (isset ( $options ['file'] )) {
            require_once ($options ['file']);
        }
        $func = $options ['function'];
        if (! empty ( $options ['class'] )) {
            $func = array (
                    $options ['class'],
                    $func 
            );
        }
        $ret = ! ! call_user_func_array ( $func, $params ) || $ret;
    }
    if (! empty ( $eventStack )) {
        $GLOBALS ['__EVENT_NAME'] = array_pop ( $eventStack );
    } else {
        unset ( $GLOBALS ['__EVENT_NAME'] );
    }
    /*
     * if ($name != 'on_log_message' && $name != 'on_autoload') { log_message("trigger event $name end", LOG_DEBUG); }
     */
    return $ret;
}

/**
 * 注册事件
 * 
 * @param $name 事件名
 *            @options array|string 事件处理函数定义, 如果是String, 'func' | 'class.func'
 *            array(
 *            'class' => '类名，optional',
 *            'function' => '函数名, required,
 *            'file' => '引入文件, optional'
 *            )
 */
function register_event($name, $options) {
    if (is_string ( $options )) {
        if (strpos ( $options, '.' ) !== FALSE) {
            $segments = explode ( '.', $options );
            $options = array (
                    'class' => $segments [0],
                    'function' => $segments [1] 
            );
        } else {
            $options = array (
                    'function' => $options 
            );
        }
    }
    if (is_array ( $name )) {
        $events = $name;
    } else {
        $events = explode ( ',', $name );
    }
    foreach ( $events as $event ) {
        SP_G::$events [$event] [] = $options;
    }
}


function checkEmail($email){
    if (preg_match("/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/",$email)){
        return true;
    }
    return false;
}

function get_event_name() {
    return isset ( $GLOBALS ['__EVENT_NAME'] ) ? $GLOBALS ['__EVENT_NAME'] : NULL;
}
function safeDivision($a, $b, $len = 2) {
    if ($b == 0) {
        return 0;
    } else {
        return round ( $a / $b, $len );
    }
}
function getSignature($data, $secretKey, $seq = '&') {
    $str = '';
    if (is_array($data)) {
        ksort ( $data );
        foreach ( $data as $key => $value ) {
            $str .= ("$key=$value" . $seq);
        }
    }
    else
    {
        $str = $data . $seq;
    }
    
    $str .= $secretKey;
    return md5 ( $str );
}
function mb_cut($str, $len, $appendix = '...') {
    if (mb_strlen ( $str ) <= $len) {
        return $str;
    }
    return mb_substr ( $str, 0, $len ) . $appendix;
}
function array_remove_empty($haystack) {
    foreach ( $haystack as $key => $value ) {
        if (is_array ( $value )) {
            $haystack [$key] = array_remove_empty ( $haystack [$key] );
        }
        
        if (empty ( $haystack [$key] )) {
            unset ( $haystack [$key] );
        }
    }
    
    return $haystack;
}
function dump() {
    $terms = func_get_args ();
    echo '<pre>';
    foreach ( $terms as $item ) {
        var_export ( $item );
    }
    echo '</pre>';
}
function genGuid() {
    static $i = 0;
    $i or $i = mt_rand ( 1, 0x7FFFFF );
    
    return sprintf ( "%08x%06x%04x%06x",
        /* 4-byte value representing the seconds since the Unix epoch. */
        time () & 0xFFFFFFFF,
 
        /* 3-byte machine identifier.
         *
         * On windows, the max length is 256. Linux doesn't have a limit, but it
         * will fill in the first 256 chars of hostname even if the actual
         * hostname is longer.
         *
         * From the GNU manual:
         * gethostname stores the beginning of the host name in name even if the
         * host name won't entirely fit. For some purposes, a truncated host name
         * is good enough. If it is, you can ignore the error code.
         *
         * crc32 will be better than Times33. */
        crc32 ( substr ( ( string ) gethostname (), 0, 256 ) ) >> 16 & 0xFFFFFF,
 
        /* 2-byte process id. */
        getmypid () & 0xFFFF,
 
        /* 3-byte counter, starting with a random value. */
        $i = $i > 0xFFFFFE ? 1 : $i + 1 );
}
function replaceMacros($str, $data) {
    $patterns = array ();
    $values = array ();
    foreach ( $data as $key => $value ) {
        $patterns [] = '/{' . $key . '}/';
        $values [] = $value;
    }
    return preg_replace ( $patterns, $values, $str );
}
function getUrlWithMacros($url, $macros, $secret_key) {
    log_message ( 'get url:' . $url, LOG_DEBUG );
    if ($url == '') {
        return FALSE;
    }
    $data = parse_url ( $url );
    
    if (empty ( $data )) {
        return FALSE;
    }
    if (empty ( $data ['query'] )) {
        return $url;
    }
    $query = replaceMacros ( $data ['query'], $macros );
    parse_str ( $query, $params );
    $params ['flag'] = getSignature ( $params, $secret_key );
    $pos = strpos ( $url, '?' );
    log_message ( 'url data:' . json_encode ( $data ) . ' params:' . json_encode ( $params ), LOG_DEBUG );
    if ($pos === FALSE) {
        return replaceMacros ( $url, $macros );
    }
    return replaceMacros ( substr ( $url, 0, $pos ), $macros ) . '?' . http_build_query ( $params );
}

/**
 * 周时间转换
 * 
 * @param [type] $dateW
 *            [description]
 * @param integer $mod
 *            [description]
 * @return [type] [description]
 */
function dateWTimestamp($dateW, $mod = 1) {
    if (! preg_match ( "/^([2-9]\d{3})(\d{2})$/", $dateW, $mathes ))
        return false;
    $year = intval ( $mathes [1] );
    $w = intval ( $mathes [2] );
    if (empty ( $year ) || empty ( $w ))
        return false;
    $t = strtotime ( $year . '-W' . $w );
    return $mod == 1 ? $t : ($t + 6 * 24 * 60 * 60);
}

/**
 * 月时间转换
 * 
 * @param [type] $dateW
 *            [description]
 * @param integer $mod
 *            [description]
 * @return [type] [description]
 */
function dateMTimestamp($dateM, $mod = 1) {
    if (! preg_match ( "/^([2-9]\d{3})(\d{2})$/", $dateM, $mathes ))
        return false;
    $year = intval ( $mathes [1] );
    $month = intval ( $mathes [2] );
    if (empty ( $year ) || empty ( $month ))
        return false;
    $t = strtotime ( $year . '-' . $month );
    $days = date ( "t", $t );
    return $mod == 1 ? $t : ($t + (($days - 1) * 24 * 60 * 60));
}
