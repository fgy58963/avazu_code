<?php
class Cimongo_Model extends Cimongo_Op
{
    protected $_dbClusterId = NULL;

	function __construct ($clusterId = NULL)
    {
        $this->_dbClusterId = $clusterId;
        $config = Cimongo_GlobalCM::getInstance($this->_dbClusterId);
        
        parent::__construct(
    		 $config['host'],
             $config['port'],
             $config['db_user'],
             $config['db_pwd'],
             $config['db_name'],
             $config['query_safety']
        );
    }
}