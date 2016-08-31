<?php
/**
 * Base class to operate database
 */
class Db_Base
{
    private  $_dbRead;
    private  $_dbWrite;
    
    private $_dbName;
    private $_dbUser;
    private $_dbPwd;
    private $_readServers;
    private $_writeServers;
    private $_res;
    
    protected $_readOnMaster = FALSE;
    
    const ERR       = -1;
    const FETCH_ALL = 0;
    const FETCH_ONE = 1;
    
    protected function __construct($dbName, $dbUser, $dbPwd, $readServers, $writeServers)
    {
        $this->_dbName      = $dbName;
        $this->_dbUser      = $dbUser;
        $this->_dbPwd       = $dbPwd;
        $this->_readServers = $readServers;
		$this->_writeServers = $writeServers;
    }

    public static function & getInstance($dbName, $dbUser, $dbPwd, $readServers, $writeServers)
    {
        $obj = new self($dbName, $dbUser, $dbPwd, $readServers, $writeServers);
        return $obj;
    }

    public function setReadOnMaster($operate = TRUE)
    {
        $this->_readOnMaster = $operate;
    }
    
    /**
     * 获取SQL查询结果
     *
     * @param string  $sql
     * @param array   $res Out parameter, array to be filled with fetched results
     * @param integer $fetchStyle 获取命名列或是数字索引列，默认为命令列
     * @param integer $fetchMode  获取全部或是一行，默认获取全部
     * @return boolean|integer false on failure, else return count of fetched rows
     */
    public function select($sql, &$res, $fetchStyle = PDO::FETCH_NAMED, $fetchMode = self::FETCH_ALL,$isCloseCursor=true)
    {
        try {
            if ($this->_readOnMaster) {
                $db = &$this->getDbWrite();
            } else {
            	$db = &$this->getDbRead();
            }
			log_message("Execute Sql: $sql on " . json_encode($this->_readServers), LOG_DEBUG);
			
			ETS::start(STAT_ET_DB_QUERY);
            $this->_res = $db->query($sql);
            
            if ($this->_res === FALSE) {
                //Log error here
                if (function_exists('log_message')) {
                    log_message('DB error:'.$sql, LOG_EMERG);
                }
                return FALSE;
            }
			ETS::end(STAT_ET_DB_QUERY, $sql);
			
            if ($fetchMode === self::FETCH_ALL) {
                $res = $this->_res->fetchAll($fetchStyle);
                return count($res);
            } else if ($fetchMode === self::FETCH_ONE) {
                $res = $this->_res->fetch($fetchStyle);
                //自动关闭Cursor，以便在同一Statement对象执行下一条语句
                if($isCloseCursor){
                $this->_res->closeCursor(); 
                }
                return $res ? 1 : 0;
            } else {
                return FALSE;
            }
        } catch (PDOException $e) {
            //Log err here
			error_report(STAT_ER_DATABASE, 'DB error:'.$e);
            return FALSE;
        }
    }

    /**
     *  获取查询结果下一行
     *  
     *  @param array $res Out parameter, array to be filled with fetched results
     *  @param integer $fetchStyle same as select method
     *  @return boolean false on failure, true on success
     */
    public function fetchNext(&$res, $fetchStyle = PDO::FETCH_NAMED)
    {
        if (!empty($this->_res)) {
            try {
                $res = $this->_res->fetch($fetchStyle);
            } catch (Exception $e) {
                //Log error here
                error_report(STAT_ER_DATABASE, 'DB query error:'.$e);
                return FALSE;
            }
            if(empty($res)){
                return false;
            }
            else {
            return TRUE;
        }

        }

        return FALSE;
    }

    /**
     * update/delete/insert/replace sql use this method
     *
     * @param string $sql sql语句
     * @param string $mode if is 'a', return affected rows count, else return boolean
     * @return boolean|integer 
     */
    public function mod($sql, $mode = '', $lock_table = FALSE, $table = '')
    {
        try {
            $db  = &$this->getDbWrite();
			
			log_message("Execute Sql: $sql", LOG_DEBUG);

			ETS::start(STAT_ET_DB_QUERY);
            if ($lock_table && $table != '') {
                $res = $db->exec('lock tables '. $table . ' write');
                if ($res === FALSE) {
                    //Log error here
                    if (function_exists('log_message')) {
                        log_message('DB mod error:'.$sql, LOG_EMERG);
                    }
                    return FALSE;
                }
            }
            $res =  $db->exec($sql);
            if ($lock_table && $table != '') {
                $db->exec('unlock tables');
            }
            if ($res === FALSE) {
                //Log error here
                if (function_exists('log_message')) {
                    log_message('DB mod error:'.$sql, LOG_EMERG);
                }
                return FALSE;
            }
			ETS::end(STAT_ET_DB_QUERY, $sql);
        } catch (PDOException $e) {
            //Log error here
			error_report(STAT_ER_DATABASE, 'DB mod error:'.$e->getMessage());
            return FALSE;
        }

        if ($mode == 'a') {
            return $res;
        }

        return TRUE;
    }

    public function & getDbWrite()
    {
		$badServerHosts = array();
		if ( ! $this->_dbWrite) {
			$this->_dbWrite =
				$this->_selectDB($this->_writeServers, $badServerHosts);
		}
        
		if ($this->_dbWrite) {
			return $this->_dbWrite;	
		}
		
		//Log error here
		error_report(
			STAT_ER_DATABASE,
			'DB Write:Connect to host(s) failed:' . implode(',', $badServerHosts),
			'DBERR:' . implode(',', $badServerHosts)
		);
		$param = FALSE;
		return $param;
    }

    public function & getDbRead()
    {
		$badServerHosts = array();
		if ( ! $this->_dbRead) {
			$this->_dbRead =
				$this->_selectDB($this->_readServers, $badServerHosts);
		}
		
		if ($this->_dbRead) {
            return $this->_dbRead;
		}
		
		//Log error here
		error_report(
			STAT_ER_DATABASE,
			'DB Read:Connect to host(s) failed:' . implode(',', $badServerHosts),
			'DBERR:' . implode(',', $badServerHosts)
		);
		
		//使用写库
		$this->_dbRead = $this->getDbWrite();
		
        return $this->_dbRead;
    }
	
	private function _selectDB($servers, &$badServerHosts = array())
	{
		//Check if it's indexed array
		if ( ! isset($servers[0])) {
			$servers = array($servers);
		}
		
		$activeServers = array();
		$badServerHosts = array();
		
		foreach ($servers as &$server) {
			if ( ! isset($server['weight'])) {
				$server['weight'] = 1;
			}
			if ($this->_isServerOk($server)) {
				$activeServers[] = $server;
			} else {
				log_message('DB Cluster:Bad status:' . $server['host'], LOG_ERR);
			}
		}
		unset($server);
		
		if (empty($activeServers)) {
			//所有服务器的状态都为不可用时，则尝试连接所有
			$activeServers = $servers;
		}
		
		$weights = 0;
		foreach ($activeServers as $server) {
			$weights += $server['weight'];
		}
		
		$dbName = $this->_dbName;
		while ($activeServers) {
			$ratio = rand(1, $weights);
			$weightLine = 0;
			$selectIndex = -1;
			//log_message("DB Cluster:select ratio:$ratio", LOG_DEBUG);
			foreach ($activeServers as $index => $server) {
				$weightLine += $server['weight'];
				if ($ratio <= $weightLine) {
					$selectIndex = $index;
					break;
				}
			}
			if ($selectIndex == -1) {
				//主机都不可用，使用weight = 0的备机
				$selectIndex = array_rand($activeServers);
			}
			$server = $activeServers[$selectIndex];
			unset($activeServers[$selectIndex]);
			log_message("DB CLUSTER: Choose server {$server['host']}:{$server['port']}.", LOG_DEBUG);
			
			$dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$dbName}";
			$pdo = NULL;
			try {	
				ETS::start(STAT_ET_DB_CONNECT);
	
				$pdo = 
					new PDO($dsn, $this->_dbUser, $this->_dbPwd, array(
							PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
							PDO::ATTR_EMULATE_PREPARES => TRUE,
							PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
							PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
							PDO::ATTR_TIMEOUT => 10,
                            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
						)
					);
					
				ETS::end(STAT_ET_DB_CONNECT, $server['host'].'.'.$dbName);	
			} catch (PDOException $e) {

                $server_addr = '';
                if(!empty($_SERVER) && isset($_SERVER["SERVER_ADDR"])){
                    $server_addr = $_SERVER["SERVER_ADDR"];
                }
				error_report(STAT_ER_DATABASE, 'DB error:'.$e->getMessage() . ', ' .$server_addr . ' to ' . $server['host']);
				$pdo = NULL;
			}
			
			$this->_setServerStatus($server, !! $pdo);
			if ($pdo) {
				return $pdo;
			} else {
				$badServerHosts[] = $server['host'];
				$weights -= $server['weight'];
			}
		}
		
		return NULL;
	}
	
	protected function _isServerOk($server)
	{
		$key = "server_status_{$server['host']}_{$server['port']}";
		if (function_exists('xcache_get')) {
			$status = @xcache_get($key);
			if (is_numeric($status)) {
				return ! empty($status);
			}
		}
        /*
		$status = Cache_Memcache::sGet($key);
		if (is_numeric($status)) {
			return ! empty($status);
		}
        */
		return TRUE;
	}
	
	protected function _setServerStatus($server, $status)
	{
		$key = "server_status_{$server['host']}_{$server['port']}";
		$status = $status ? '1' : '0';
		$cacheTime = 60; //1 min
		
		if (function_exists('xcache_get')) {
			if (@xcache_get($key) === $status) {
				//log_message('DB Cluster:set server status ignor', LOG_DEBUG);
				return;
			}
			//二级缓存
			@xcache_set($key, $status, 10);
		}
		
        /*
		$failCountKey = "server_fail_count_{$server['host']}_{$server['port']}";
		if ($status === '1') {
			Cache_Memcache::sSet($failCountKey, '0');
		} else {
			if (Cache_Memcache::sGet($key) !== '0') {
				$failCount = Cache_Memcache::sGet($failCountKey);
				$failCount = empty($failCount) ? 0 : intval($failCount);
				$failCount++;
				$cacheTime *= pow(2, min($failCount - 1, 8)); //Max 256 min
				Cache_Memcache::sSet($failCountKey, "$failCount");
			}
		}
		
		Cache_Memcache::sSet($key, $status, $cacheTime);
		log_message(
			"DB Cluster:set status {$server['host']}:$status, cache time:$cacheTime",
			$status === '1' ? LOG_DEBUG : LOG_ERR
		);
        */
	}
	
    public function getDbName()
    {
        return $this->_dbName;
    }

    /**
     * 获取上次insert操作时得到的自增id
     *
     * @return integer
     */
    public function getLastId()
    {
    	if ($this->_dbWrite) {
            return $this->_dbWrite->lastInsertId();
    	}
    	return 0;
    }

    /**
     * 获取sql读取错误信息
     *
     * @return string
     */
    public function getReadErrorInfo()
    {
        if (!$this->_readOnMaster) {
            $db = $this->_dbRead;
        } else {
            $db = $this->_dbWrite;
        }

        if (!empty($db)) {
            $err = $db->errorInfo();
            return $err[2];
        }

        return "Db Reader Not initiated\n";
    }

    /**
     * 获取sql写入错误信息
     *
     * @return string
     */
    public function getWriteErrorInfo()
    {
        if (!empty($this->_dbWrite)) {
            $err = $this->_dbWrite->errorInfo();
            return $err[2];
        }

        return "DB Writer not initiated\n";
    }
    
    /**
     * 判断上次错误是否由于重复key引起
     *
     * @return boolean
     */
    public function isDuplicate()
    {
        if (!empty($this->_dbWrite)) {
            $err = $this->_dbWrite->errorInfo();
            return $err[1] == 1062;
        }

        return FALSE;
    }

    public function close()
    {
        if ($this->_dbWrite) {
            $this->_dbWrite = NULL;
        }
        if ($this->_dbRead) {
            $this->_dbRead = NULL;
        }
    }
}
