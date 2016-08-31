<?php
/**
 * 
 */
class Util_Tool{

	private static $_instance = null;

	public function __construct() 
	{
	}

	public static function getInstance() {
		if (self::$_instance === NULL) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 取一次会话的标记
	 * @return [type] [description]
	 */
	public function getLogSessionKey() {
		if (empty($this->_logSessionKey)) {
			$this->_logSessionKey = microtime(true);
		}

		return $this->_logSessionKey;
	}


	//生成随机密码
	static function generatePassword($length = 8) {

		// 密码字符集，可任意添加你需要的字符
		// $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_';
		$chars        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$chars_length = strlen($chars);

		$password = '';
		for ($i = 0; $i < $length; $i++) {
			// 这里提供两种字符获取方式
			// 第一种是使用 substr 截取$chars中的任意一位字符；
			// 第二种是取字符数组 $chars 的任意元素
			// $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			$password .= $chars[mt_rand(0, $chars_length - 1)];
		}

		return $password;

	}
	
	/**
	 * 取指定脚本进程数
	 * @param  string $shellName 要 ps 的脚本名
	 * @return  int   找不到返回 0,否则返回 进程数
	 */
	public static function processCount($shellName1,$shellName2='') {

		$tmp_cmd = 'ps -ef |grep "' . preg_quote($shellName1).'"';
        if(!empty($shellName2)){
            $tmp_cmd.= ' | grep "' . preg_quote($shellName2).'"';
        }
         $tmp_cmd.= ' | grep -v grep | wc -l';
		exec($tmp_cmd, $output, $tmp_ret);

        $process_count = 0;
		if (is_array($output)) {
            $process_count = intval(current(($output)));
		}
        log_message("tmp_cmd: {$tmp_cmd}, process_count: {$process_count}", LOG_DEBUG);
        // echo ("tmp_cmd: {$tmp_cmd}, process_count: {$process_count}");
		return $process_count;

	}	
}