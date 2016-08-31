<?php
class Db_Model extends Model_Base
{
    protected static $_forceReadOnMater = FALSE;

    protected $_table = NULL;
    protected $_dbClusterId = NULL;
    protected $_readOnMaster = FALSE;
    //Used with farm db
    protected $_objectId = NULL;

    protected $_eventHandlers = array();

    private $_dbInstance = NULL;
    public         $_sqlHelper = NULL;
    private $_lastSql;
    protected $_serverErrorCode = 'app.server.db.error!';
    // const SERVER_ERROR = 'app.server.db.error!';

    /**
     * 构造器
     *
     * @param object $logger default NULL,日志记录对象
     * @param string $table default NULL, 表名，为NULL则不能使用基类提供的数据库操作方法
     * @param int $clusterId default NULL, 数据库cluster id
     * @param int $objectId default NULL, 对象id，用于分库选取用，单库不需要设置此参数
     */
    function __construct ($table = NULL, $clusterId = NULL, $objectId = NULL)
    {
        log_message("table: {$table} , clusterId:{$clusterId}, objectId:{$objectId}",LOG_DEBUG);
        $this->_table = $table;
        $this->_dbClusterId = $clusterId;
        $this->_objectId = $objectId;
        $this->_sqlHelper = Db_Sql::getInstance();
    }

    //设置所有的Model都强制读写主库
    public static function setForceReadOnMater ($bool = TRUE)
    {
        Db_Model::$_forceReadOnMater = $bool;
    }

    public function getLastId()
    {
        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        return $db->getLastId();
    }

    public function mutiInsert($row , $rowData ,  $returnLastId = false, $lock_table = false, $ignore = false)
    {
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }
        $sql = 'INSERT ' . ($ignore ? ' IGNORE ' : '') . $this->_table() . $this->_sqlHelper->insert($row , $rowData);
        $ret = $db->mod($sql, '',$lock_table, $this->_table);
        $this->_lastSql = $sql;
        if ($ret === FALSE) {
            $this->log("[$sql] " . $db->getWriteErrorInfo(), LOG_ERR);
            NotifyMonitor::notifyError($this->_serverErrorCode,"DB error mutiInsert:[$sql] " . $db->getWriteErrorInfo());
            return FALSE;
        }
        $lastId = 0;
        if ($returnLastId) {
            $lastId = $db->getLastId();
        }
        return $returnLastId ? $lastId : $ret;
    }


    public function insert ($insArr, $returnLastId = FALSE, $ignore = FALSE)
    {
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        $this->beforeInsert($insArr);

        $sql = 'INSERT ' . ($ignore ? ' IGNORE ' : '') . $this->_table() . $this->_sqlHelper->insert($insArr);

        $ret = $db->mod($sql);
        $this->_lastSql = $sql;
        if ($ret === FALSE) {

            $tmp_db_err = $db->getWriteErrorInfo();
            $this->log("[$sql] " . $tmp_db_err, LOG_ERR);

            if(false === strpos($tmp_db_err,"Duplicate entry")){
                NotifyMonitor::notifyError($this->_serverErrorCode,"DB error insert:[$sql] " . $tmp_db_err);
            }
            return FALSE;
        }

        $lastId = 0;
        if ($returnLastId) {
            $lastId = $db->getLastId();
        }

        $this->afterInsert($insArr, $lastId);

        return $returnLastId ? $lastId : $ret;
    }

    public function insertReplace ($insArr, $replaceArr = NULL)
    {
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        $this->beforeInsertReplace($insArr, $replaceArr);

        $sql = 'INSERT ' . $this->_table() . $this->_sqlHelper->replace($insArr, $replaceArr);

        $ret = $db->mod($sql, 'a');
        $this->_lastSql = $sql;

        if ($ret === FALSE) {
            $this->log("[$sql] " . $db->getWriteErrorInfo(), LOG_ERR);
            NotifyMonitor::notifyError($this->_serverErrorCode,'DB error insertReplace:'."[$sql] " . $db->getWriteErrorInfo());
            return FALSE;
        }

        $this->afterInsertReplace($insArr, $replaceArr, $ret);

        return $ret;
    }

    public function update ($where, $uptArr, $orderStr = null)
    {
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        $this->beforeUpdate($where, $uptArr);

        $sql = 'UPDATE ' . $this->_table() . $this->_sqlHelper->update($uptArr) . $this->_sqlHelper->where($where);
        if (!empty($orderStr)) {
            $sql .= ' ' . $orderStr;
        }
        $ret = $db->mod($sql, 'a');
        $this->_lastSql = $sql;

        if ($ret === FALSE) {
            $this->log("[$sql] " . $db->getWriteErrorInfo(), LOG_ERR);
            NotifyMonitor::notifyError($this->_serverErrorCode,'DB error update:'."[$sql] " . $db->getWriteErrorInfo());
            return FALSE;
        }

        $this->afterUpdate($where, $uptArr);

        return $ret;
    }

    public function delete ($where)
    {
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        $this->beforeDelete($where);

        $sql = 'DELETE FROM ' . $this->_table() . $this->_sqlHelper->where($where);

        $ret = $db->mod($sql);
        $this->_lastSql = $sql;

        if ($ret === FALSE) {
            $this->log("[$sql] " . $db->getWriteErrorInfo(), LOG_ERR);
            NotifyMonitor::notifyError($this->_serverErrorCode,'DB error delete:'."[$sql] " . $db->getWriteErrorInfo());
            return FALSE;
        }

        $this->afterDelete($where);

        return $ret;
    }

    public function addEventHandler($handlerObj)
    {
        $class = get_class($handlerObj);
        if ( ! isset($this->_eventHandlers[$class])) {
            $this->_eventHandlers[$class] = $handlerObj;
        }
    }

    protected function beforeInsert (&$data)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->beforeInsert($this, $data);
        }
    }

    protected function afterInsert ($data, $lastId)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->afterInsert($this, $data, $lastId);
        }
    }

    protected function beforeUpdate (&$where, &$data)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->beforeUpdate($this, $where, $data);
        }
    }

    protected function afterUpdate ($where, $data)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->afterUpdate($this, $where, $data);
        }
    }

    protected function beforeInsertReplace (&$data, &$replace)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->beforeInsertReplace($this, $data, $replace);
        }
    }

    protected function afterInsertReplace ($data, $replace, $ret)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->afterInsertReplace($this, $data, $replace, $ret);
        }
    }

    protected function beforeDelete (&$where)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->beforeDelete($this, $where);
        }
    }

    protected function afterDelete ($where)
    {
        foreach ($this->_eventHandlers as $handler) {
            $handler->afterDelete($this, $where);
        }
    }

    /**
     * 
     * @param  array
     * @param  array
     * @param  [type]
     * @param  [type] Db_Base::FETCH_ALL = 0;
     * @param  boolean $isCloseCursor  自动关闭Cursor，以便在同一Statement对象执行下一条语句
     * @return [type]
     */
    public function select ($where = array(), $attrs = array(),$fetchStyle = PDO::FETCH_NAMED, $fetchMode = 0,$isCloseCursor=true)
    {
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        if (is_callable(array(
            $this,
            'beforeSelect'
        ), TRUE)) {
            $this->beforeSelect($where, $attrs);
        }

        $selectFields = isset($attrs['select']) ? $attrs['select'] : '*';

        $sql = "SELECT {$selectFields} FROM " . $this->_table() . $this->_sqlHelper->where($where, $attrs);
        $res = NULL;
        $this->_lastSql = $sql;

        if ($db->select($sql, $res,$fetchStyle,$fetchMode,$isCloseCursor) === FALSE) {
            $this->log("[$sql] " . $db->getReadErrorInfo(), LOG_ERR);
            NotifyMonitor::notifyError($this->_serverErrorCode,"DB error select:[$sql] " . $db->getWriteErrorInfo());
            return FALSE;
        }

        if (is_callable(array(
            $this,
            'afterSelect'
        ), TRUE)) {
            $this->afterSelect($res);
        }

        return $res;
    }

    /**
     *  获取查询结果下一行
     *  
     *  @param array $res Out parameter, array to be filled with fetched results
     *  @param integer $fetchStyle same as select method
     *  @return boolean false on failure, true on success
     */
    public function fetchNext(&$res, $fetchStyle = PDO::FETCH_NAMED){
        if ($this->_table === NULL) {
            return FALSE;
        }

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }
       if ($db->fetchNext($res, $fetchStyle) === FALSE) {
            $this->log("fetchNext error," . $db->getReadErrorInfo(), LOG_ERR);
            return FALSE;
        }
        return true;

      }



    public function selectOne ($where = array(), $attrs = array())
    {
        $attrs['limit'] = 1;
        $attrs['offset'] = 0;

        $res = $this->select($where, $attrs);
        if ($res === FALSE) {
            return FALSE;
        }
        if (empty($res)) {
            return NULL;
        }
        return $res[0];
    }

    public function selectCount ($where = array(), $attrs = array())
    {
        if (! isset($attrs['select'])) {
            $attrs['select'] = 'COUNT(0)';
        }
        $attrs['select'] .= ' AS `total`';

        $res = $this->selectOne($where, $attrs);
        if ($res === FALSE) {
            return FALSE;
        }
        return intval($res['total']);
    }

    /**
     * 返回记录行、总数、及分页HTML代码的数组
     *
     * @param mixed $where
     * @param mixed $attrs
     * @param int $pageNo 取第几页
     * @param int $limit 每次取几条
     * @access public
     * @return array()
     */
    public function getListDataByPage($where, $attrs, $pageNo,$limit='') 
    {
        if (empty($limit) ) {
            if(isset($attrs['limit'])){
                $limit = $attrs['limit'];
            }
        }else{
            $attrs['limit'] = $limit;
        }

        $pageNo  = intval($pageNo);
        if ( empty($pageNo)  ) 
        {
            $pageNo = 1;
        }

        $attrs['offset'] =  ($pageNo - 1 ) * $limit;

        return $this->getListData($where, $attrs);

    }

    /**
     * getListData 返回记录行、总数、及分页HTML代码的数组
     *
     * @param mixed $where
     * @param mixed $attrs
     * @param mixed $data 如果传入该参数的话，则直接将返回数据设置在此变量中
     * @access public
     * @return array()
     */
    public function getListData($where, $attrs, &$data = NULL)
    {
        $items = $this->select($where, $attrs);
        $total = 0;
        $pagination = '';
        $pageSize = 50;
        if (isset($attrs['limit'])) {
            $total = $this->selectCount($where);
            $pageSize = $attrs['limit'];
            $pagination = Util_Pagination::getHtml($total, $pageSize);
        }

        if ( ! is_null($data)) {
            $data['items'] = $items;
            $data['totalCount'] = $total;
            $data['pagination'] = $pagination;
            return;
        }

        return array(
            'items' => $items,
            'pageSize' => $pageSize,
            'totalCount' => $total,
            'pagination' => $pagination,
        );
    }

    /**
     * find 主键查询
     *
     * @param mixed $primaryKeys 单个值或是数组值
     * @param string $primaryKeyName
     * @access public
     * @return mixed 主键传入单个值时，返回单行记录，多行值返回以主键值为Key的关联数组
     */
    public function find($primaryKeys, $primaryKeyName = 'id')
    {
        if (empty($primaryKeys)) {
            return array();
        }

        $needArray = is_array($primaryKeys);

        if ($needArray) {
            $primaryKeys = array_unique($primaryKeys);
        }

        $rows = $this->select(array(
            $primaryKeyName => $primaryKeys,
        ));

        if ( ! $needArray) {
            return $rows ? $rows[0] : array();
        }

        $ret = array();
        foreach ($rows as $row) {
            $ret[$row[$primaryKeyName]] = $row;
        }

        return $ret;
    }

    /**
     * Execute sql statement:
     * For select statement, return the rows;
     * For non-select statement, return rows affected;
     * When error, return false
     *
     * @param string $sql
     */
    public function execute ($sql)
    {
        $method = @strtoupper(array_shift(explode(' ', trim($sql))));

        $db = $this->_getDbInstance();
        if (! $db) {
            return FALSE;
        }

        if (in_array($method, array(
            'SELECT',
            'SHOW',
            'DESC'
        ))) {
            $res = NULL;
            if ($db->select($sql, $res) === FALSE) {
                $this->log("[$sql] " . $db->getReadErrorInfo(), LOG_ERR);
                NotifyMonitor::notifyError($this->_serverErrorCode,"DB error execute:[$sql] " . $db->getWriteErrorInfo());
                return FALSE;
            }
            return $res;
        } else {
            $ret = $db->mod($sql, 'a');
            $this->_lastSql = $sql;
            if ($ret === FALSE) {
                $this->log("[$sql] " . $db->getWriteErrorInfo(), LOG_ERR);
                NotifyMonitor::notifyError($this->_serverErrorCode,"DB error execute:[$sql] " . $db->getWriteErrorInfo());
                return FALSE;
            }
            return $ret;
        }
    }

    /**
     * Magic函数
     * 用于实现 get_by_xxx/getByXxx方法
     */
    public function __call ($name, $args)
    {
        if (strpos($name, 'get_by_') === 0) {
            $key = substr($name, 7);
            $value = $args[0];
            return $this->selectOne(array(
                $key => $value
            ));
        } else
            if (strpos($name, 'getBy') === 0) {
                $key = strtolower(substr($name, 5));
                if ($key) {
                    $where = array(
                        $key => $args[0]
                    );
                    return $this->selectOne($where);
                }
            } else
                if (strpos($name, 'before') === 0 || strpos($name, 'after') === 0) {
                    return TRUE;
                }
        trigger_error('Undefined method ' . $name . ' called!');
        return FALSE;
    }

    public function setReadOnMaster ($bool = TRUE)
    {
        $this->_readOnMaster = $bool;
        if ($this->_dbInstance) {
            $this->_dbInstance->setReadOnMaster($bool);
        }
    }

    public function getTable ()
    {
        return $this->_table;
    }

    public function table ($table = NULL)
    {
        if (empty($table)) {
            return $this->_table;
        }
        $this->_table = $table;
    }

    public function getDatabaseName()
    {
        $db = $this->_getDbInstance();
        if ($db) {
            return $db->getDbName();
        }

        return NULL;
    }

    private function _table()
    {
        $tables = $this->_table;
        if (is_string($this->_table)) {
            $tables = array($this->_table);
        }

        $arr = array();
        foreach ($tables as $table) {
            if (preg_match('@^\w+$@', $table)) {
                $arr[] = "`{$table}`";
            } else {
                $arr[] = $table;
            }
        }

        return implode(',', $arr);
    }

    public function getLastSql ()
    {
        return $this->_lastSql;
    }

    protected function _getDbInstance ()
    {
        if ($this->_dbInstance) {
            return $this->_dbInstance;
        }

        if ($this->_dbClusterId !== NULL) {
            if ($this->_objectId !== NULL) {
                //It's farm db
                $this->_dbInstance = Db_FarmDb::getInstanceByObjectId($this->_objectId, $this->_dbClusterId);
            } else {
                $this->_dbInstance = Db_GlobalDb::getInstance($this->_dbClusterId);
            }
            $this->_dbInstance->setReadOnMaster(Db_Model::$_forceReadOnMater || $this->_readOnMaster);
            return $this->_dbInstance;
        }

        return NULL;
    }

    public function __destruct ()
    {
        if ($this->_dbInstance) {
            $this->_dbInstance->close();
            $this->_dbInstance = NULL;
        }
        $this->_sqlHelper = NULL;
    }

    /**
     * 
     * @param unknown $where
     * @param unknown $select
     * @param unknown $pageno 当前第几页
     * @param number $limit
     * @return Ambigous <boolean, NULL>
     */
    public function getList($where, $select,  $pageno=1, $limit=100)
    {
    	
    	if(intval($pageno) <1){
    		$pageno = 1;
    	}
    	if($limit < 1){ $limit =  100;}
    	$offset = ($pageno -1) * $limit;
    	
    	
    	if(!is_array($select)){
    		$select = array('select' => $select);
    	}
        $select['offset'] = $offset;
        $select['limit'] = $limit;
        

        return $this->select($where, $select);
    }
    
    /**
     * 返回 以 [key] => [value] 格式的数据
     * @param unknown $where
     * @param unknown $keyField
     * @param unknown $valField
     * @param number $offset
     * @param number $limit
 	 * @param string $emptyTitle 添加时，默认显示空选项对应的标题
     */
    public function getListForKeyVal($where, $emptyTitle='',$keyField='id',$valField='name',  $offset=0, $limit=10000,$order='')
    {
    	$select = array('select' => $keyField.','.$valField);
    	$select['offset'] = $offset;
    	$select['limit'] = $limit;
    	if(!empty($order)){
    		$select['order_by'] = $order;
    	}
    	 
    	$return_array = array();
    	if(!empty($emptyTitle)){
    		$return_array[''] = $emptyTitle;
    	}
    	
    	$result_array =  $this->select($where, $select);
        if($result_array){
    	foreach ($result_array as $record){
    		$return_array[$record[$keyField]] = $record[$valField];
    	}
        }

    	return $return_array;
    }
    
    

    public function getItem($id, $select = '*')
    {
        if(!is_array($select)){
            $select = array($select);
        }
        $select = array('select' => implode($select, ','));
          return $this->selectOne(array('id' => $id), $select);
    }

    public function updateItem($id, $attr)
    {
        return $this->update(array('id' => $id), $attr);
    }

    public function addItem($attr)
    {
        return $this->insert($attr, TRUE);
    }

    public function existItem($where)
    {
        return $this->selectCount($where) > 0;
    }

    public function saveItem($id, $attr)
    {
        return empty($id) ? $this->addItem($attr) : $this->updateItem($id, $attr);
    }

    public function loadDataInFile($fieldNames, $fileName, $ignoreLines = 0, $replace = FALSE, $fieldEncloseBy='', $fieldSep = '\t', $lineSep = '\n')
    {
        $tableName = $this->getDatabaseName() . '.' . $this->getTable();
        $typeName = $replace  ? 'REPLACE' : 'IGNORE';
        $fields = '`' . implode('`,`', $fieldNames) . '`';
        $sql = "load data local infile '{$fileName}'  {$typeName} into table {$tableName}  character set utf8 FIELDS TERMINATED BY '{$fieldSep}' ENCLOSED BY '{$fieldEncloseBy}' LINES TERMINATED BY '{$lineSep}' IGNORE {$ignoreLines} LINES  ({$fields});";
        $ret = $this->execute($sql);
        log_message("loadDataInFile, cmd:{$sql}, ret:" . json_encode($ret), ($ret === FALSE ? LOG_ERR : LOG_INFO));
        return $ret;
    }
}
