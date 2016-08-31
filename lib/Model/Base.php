<?php
class Model_Base
{
    public function __construct()
    {
        
    }

    public function log($msg, $pri = LOG_INFO)
    {
        log_message($msg, $pri);
    }
}