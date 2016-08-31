<?php
class Util_ScribeLog
{
    public static $STATUS_OK = TRUE;

    private $_transport = NULL; 
    private $_client = NULL; 

    private function __construct()
    {
        $config = Config::get('Scribe');

        if ( ! empty($config)) {
            $host = $config['host'];
            $port = isset($config['port']) ? $config['port'] : 1463;
            
            try {
                $socket = new TSocket($host, $port, TRUE);
                $this->_transport = new TFramedTransport($socket);
                $protocol = new TBinaryProtocol($this->_transport, FALSE, FALSE);
                $this->_client = new scribeClient($protocol, $protocol);
                $this->_transport->open();
            } catch (Exception $e) {
                self::$STATUS_OK = FALSE;            
                log_message('Scribe transport open failed:' . $e, LOG_ERR);
                $this->_client = NULL;
            }

        } else {
            self::$STATUS_OK = FALSE;
            log_message('Scribe config not found!');
        }
    }
    
    public function send($msg, $logCategory)
    {
        if (empty($this->_client)) {
            return;
        }

        $send = array(
            'category' => $logCategory,
            'message' => $msg 
        );
        $entry = new LogEntry($send);
        $messages = array(
            $entry 
        );  
        try {
            $this->_client->Log($messages);
        } catch (Exception $ex) {
            self::$STATUS_OK = FALSE;
            log_message('Scribe log fail:' . $ex, LOG_ERR);
        }
    }

    public function __destruct()
    {
        if ($this->_transport  && $this->_transport->isOpen()) {
            $this->_transport->close();
            $this->_transport = NULL;
        }
        $this->_client = NULL;
    }

    public static function sendLog($msg, $logCategory)
    {
        self::getInstance()->send($msg, $logCategory); 
    }

    public static function getInstance()
    {
        static $instance = NULL; 

        if ($instance === NULL) {
            $instance = new self();
        }

        return $instance;
    }
}
