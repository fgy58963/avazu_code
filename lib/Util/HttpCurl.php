<?php
class Util_HttpCurl{

    const HEAD_HTTP = 'HTTP_';
    private $_connectTimeout = 5;
    private $_resultHttpCode;

    // 内容下载完成超时时间
    private $_timeout = 500;

    public function setTimeOunt($time){
        $this->_timeout = $time;
    }
    public function setConnectTimeout($time){
        $this->_connectTimeout = $time;
    }

    public function __construct($timeout=500,$connectTimeout=5) {

        $this->_timeout = $timeout;
        $this->_connectTimeout = $connectTimeout;
    }

	public function httpRequest($url, $headerParam)
    {
    	if (empty($url)) {
            return;
        }
        $parse_url = parse_url($url);
        $scheme = "http";
        if (isset($parse_url['scheme'])) {
            $scheme = $parse_url['scheme'];
        }

        $headerArr = $this->buidHead($headerParam);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 3000);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2000);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);

        curl_setopt($ch, CURLOPT_POST, FALSE);
        if ($scheme == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

	public function httpPost($url, $post_data = array(),$headerParam=array()){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_connectTimeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        if(!empty($headerParam)){
            $headerArr = $this->buidHead($headerParam);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
	}

	public function buidHead($param)
	{
		$headerArr = array();
		foreach ($param as $key => $value) {
			$header = $key . ":" . $value;
			$headerArr[] = $header;
            // log_message('data header:' . $header, LOG_DEBUG);
		}
		return $headerArr;
	}

    /**
     * curl 的基础封装
     * @param  array $options 项目
     * @param  string $message 错误引用变量
     * @return bool|string  成功返回内容   
     * @return array rightHttpCodes 认为正常的http码
     */
    protected function _curl($options,&$message,$rightHttpCodes=200)
    {
        if(empty($rightHttpCodes)){
            $rightHttpCodes = Array(200);
        }else if(!is_array($rightHttpCodes)){
            $rightHttpCodes = Array($rightHttpCodes);
        }

        $ch = curl_init();
        if(false == $ch)
        {
            $message = 'curl_init error';
            return false;
        }

        curl_setopt_array ($ch, $options);

        $content = curl_exec($ch);
        if(false === $content){
            $message =  curl_error($ch);
            return false;
        }
                
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // var_dump($status_code);
        // print_r($options);

        $this->_resultHttpCode = $status_code;
        if( !in_array($status_code, $rightHttpCodes))
        {
            $message = curl_getinfo($ch);
            $errMsg   = "curl ERROR ,status_code:{$status_code}, message:".json_encode($message);
            log_message($errMsg,LOG_DEBUG);
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $content;
    }

    public function getResultHttpCode(){
        return $this->_resultHttpCode;
    }
    /**
     * 检测ＵＲＬ状态，如果非２００返回错误，并设置message
     * @param string $url   //url
     * @param string $message 出错信息
     * @return boolen          
     */
    public function urlCheck($url,&$message,$options=array(),$rightHttpCodes=200,$charset='utf-8')
    {

        $header = array(
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=" . $charset,
            "Content-transfer-encoding: text"
        );

        $default_options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false, //获取http头信息
            CURLOPT_NOBODY => true, //不返回html的body信息

            CURLOPT_CONNECTTIMEOUT => $this->_connectTimeout,
            CURLOPT_TIMEOUT => $this->_timeout,

            CURLOPT_HTTPHEADER => $header,
            CURLOPT_USERAGENT => 'MISE 6.0',
            CURLOPT_RETURNTRANSFER => 1,

        );

        if(0 ===strpos($url, 'https://')){
              $default_options[CURLOPT_SSL_VERIFYPEER] = FALSE; 
        }


        //合并参数
        foreach ($default_options as $key => $value) {
            if(isset($options[$key]) && is_array($options[$key]) && is_array($value) )
            {   
                $options[$key] = array_merge($options[$key],$value);
            }   
            else
                $options[$key] = $value;

        }   

  
        $content = $this->_curl($options, $message,$rightHttpCodes);
        if(false === $content)
            return false;
        return true;

    }

    public static function getHead($headers=''){

        $headers = empty($headers) ? $_SERVER : $headers;
        $keys = array_keys($headers);
        $keys = preg_grep("/^".self::HEAD_HTTP."/si" , $keys);
        $needHeader = array();
        foreach ($keys as $key) {
            if(isset($headers[$key]))
            {
                $k = str_replace(self::HEAD_HTTP, "" , $key);
                $needHeader[$k] = $headers[$key];
            }
        }
        return $needHeader;
    }
}