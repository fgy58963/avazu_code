<?php
class Protocol_ProtoBuf
{
	const  PROTOCOL_TYPE = 'protobuf';
	const  FILE_TYPE = '.php';
	public $protoData = '';
	public $filePrefix = 'pb_proto_';
	public $className = '';
	public $fileName = '';
	public $filePath = '';
	public static $loadProto = array();
	public static $classProto = array();

    public function __construct($protoData = '' , $filePrefix = '')
    {
        if (!extension_loaded('protobuf'))
        {
            trigger_error("The protocol_protobuf:not load protobuf.so!", E_USER_ERROR);
            exit();
        }
    	$protoInfo = Config::get('protocol_data');
    	if (!empty($protoData)) $this->protoData = $protoData;
    	if (!empty($filePrefix)) $this->filePrefix = $filePrefix;
    	if (is_array($protoInfo) && isset($protoInfo[self::PROTOCOL_TYPE]))
    	{
    		$protoInfo  = $protoInfo[self::PROTOCOL_TYPE];
    		$this->protoData = empty($this->protoData) ? $protoInfo['data'] : $this->protoData;
    		$this->filePrefix = empty($this->filePrefix) ?  $protoInfo['prefix'] : $this->filePrefix;
    	}
    	$this->protoData = rtrim($this->protoData,'/').'/';
    }

    private function setClass($name)
    {
    	if (empty($name)) return false;
    	$this->className = ucfirst($name);
    	$this->fileName = $this->filePrefix.$name.self::FILE_TYPE;
    	$this->filePath = $this->protoData.$this->fileName;
    	return true;
    }

    public function loadProto($name)
    {
    	if ($this->setClass($name) === false) return false;
    	if (isset(self::$loadProto[$this->filePath]))
    	{
    		return true;
    	}
    	if (!file_exists($this->filePath)) {
    		return false;
    	}
    	require_once($this->filePath);
    	self::$loadProto[$this->filePath]  = true;
    	return true;
    }

    public function getProto($name)
    {
    	if ($this->loadProto($name) === false) return false;
    	if (isset(self::$classProto[$this->className])) return self::$classProto[$this->className];
    	if (class_exists($this->className)) self::$classProto[$this->className] = new $this->className();
    	return isset(self::$classProto[$this->className]) ? self::$classProto[$this->className] : false;
    }

}