<?php
class NotifyMonitor
{
	const MONITOR_URL = 'http://api.monitor.avazu.net/alert?';
	/*
		$name : 脚本名字
		$content:报警内容
	*/
	public static function notify($name, $content,$success='no'){
		if (in_array(ENV, array('LOCAL','DEVELOPMENT','TEST'))) {
			// var_dump($content);
			log_message(sprintf('request log '.$success.', name:%s, msg:%s'.PHP_EOL, $name, $content), LOG_ERR);
			return FALSE;
		}
	    $params = array(
	        'name' => $name,
	        'success' => $success,
	        'msg' => $content
	    );
	    $url = self::MONITOR_URL.http_build_query($params);
	    $ret = http_get($url, FALSE);
	   	$ret = json_decode($ret);
	    if(is_array($ret) && $ret['code'] != 0){
	        log_message(sprintf('request log failed, name:%s, msg:%s'.PHP_EOL, $name, $content), LOG_ERR);
	    }
	}

	protected static function _notifyWithHost($name, $msg, $hostName = '', $succ = 'no') {
		if (empty($hostName))
		{
			$hostName = isset($_SERVER['HTTP_HOST']) ? rtrim($_SERVER['HTTP_HOST'],'/') : '';
			if (empty($hostName))
			{
				if (isset($argv) && isset($argv[0]))
				{
					$hostName = $argv[0];
				}
			}
		}
		$msg = '<b>'.$hostName.':</b>'.$msg;
		return self::notify($name , $msg, $succ);
	}

	public static function notifySucc($name, $msg, $hostName = '')
	{
		return self::_notifyWithHost($name, $msg, $hostName, 'yes');
	}

	public static function notifyError($name, $msg , $hostName = '')
	{
		return self::_notifyWithHost($name, $msg, $hostName, 'no');
	}

    /**
     * 计划任务
     * @param  [type] $name     [description]
     * @param  [type] $msg      [description]
     * @param  string $hostName [description]
     * @return [type]           [description]
     */
    public static function notifyTask($name,$msg = '', $hostName = '')
    {
		return self::_notifyWithHost($name, $msg, $hostName, 'yes');
    }	
}