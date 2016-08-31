<?php
class MCrypt {
    
    private $iv;
    private $key;
    private $bit;

    public function __construct($key, $bit = 128, $iv = "") {
        if($bit == 256){
            $this->key = hash('SHA256', $key, true);
        }else{
            $this->key = hash('MD5', $key, true);
        }
        if($iv != ""){
            $this->iv = hash('MD5', $iv, true);
        }else{
            $this->iv = chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
        }
    }

    public function hexToStr($hex)     
    {     
        $bin="";     
        for($i=0; $i<strlen($hex)-1; $i+=2)     
        {    
            $bin.=chr(hexdec($hex[$i].$hex[$i+1]));     
        }    
        return $bin;     
    } 

    public function encrypt($str) {
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $this->key, $this->iv);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC); 
        $pad = $block - (strlen($str) % $block);
        $str .= str_repeat(chr($pad), $pad);
        $encrypted = mcrypt_generic($module, $str);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return bin2hex($encrypted);
    }
    
    public function decrypt($str) {   
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $this->key, $this->iv);
        $str = mdecrypt_generic($module, $this->hexToStr($str)); 
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        $slast = ord(substr($str, -1));
        $str = substr($str, 0, strlen($str) - $slast);
        return $str;
    }

    /**
    * 加密base64编码的字符串
    **/
    public function encrypt2Base64($str) {
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $this->key, $this->iv);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC); 
        $pad = $block - (strlen($str) % $block);
        $str .= str_repeat(chr($pad), $pad);
        $encrypted = mcrypt_generic($module, $str);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return base64_encode($encrypted);
    }


    /**
     * 解密
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public function decryptFromBase64($str)
    {
        $str = base64_decode($str);
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($module, $this->key, $this->iv);
        $str = mdecrypt_generic($module, $str); 
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        $slast = ord(substr($str, -1));
        $str = substr($str, 0, strlen($str) - $slast);
        return $str;
    }
}