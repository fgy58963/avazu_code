<?php
class Db_DataModificationLog extends Db_EventHandler
{
    private $_primaryKey = 'id';
    private $_logModel = NULL;
    private $_cache = array();
    private $_ignoreFields = array();

    const OP_TYPE_INSERT = 1;
    const OP_TYPE_DELETE = 2;
    const OP_TYPE_UPDATE = 3;

    public static $op_type = array(
        self::OP_TYPE_INSERT => 'Insert',
        self::OP_TYPE_DELETE => 'Delete',
        self::OP_TYPE_UPDATE => 'Update',
    );

    public function __construct($logModel, $primaryKey = 'id', $ignoreFields = array('last_update_time'))
    {
        $this->_logModel = $logModel;
        $this->_primaryKey = $primaryKey;
        $this->_ignoreFields = $ignoreFields;
    }



    private function _addLog($dbName, $table, $type, $objectId, $originData, $newData = array())
    {
        $changeData = array();

        if ($type == self::OP_TYPE_UPDATE) {
            foreach ($originData as $field => $value) {
                if (in_array($field, $this->_ignoreFields)) {
                    continue;
                }
                if (isset($newData[$field]) && $newData[$field] != $value) {
                    $changeData[$field] = $newData[$field];
                }
            }
            if (empty($changeData)) {
                return FALSE;
            }
        }

        $changeFields = array_keys($changeData);

        $config = AppRunner::$config;

        $account_id = $config['sso_uid'];

        $ins = array(
            'db_name' => $dbName,
            'table_name' => $table,
            'email' => $config['email'],
            'sso_uid'=> $config['sso_uid'],            
            'account_id' => $account_id,
            'op_type'=> $type,
            'ip' => $config['ip'],
            'object_id' => $objectId,
            'business_type' => '',
            'origin_data'   => json_encode($originData),
            'change_fields' => implode(',', $changeFields),
            'change_data' => (empty($changeData) ? '' : json_encode($changeData)),
            'create_time' => time(),
        );
        return $this->_logModel->insert($ins);
    }

    public function afterInsert ($model, $data, $lastId) {
        $model->setReadOnMaster(TRUE); 
        $table = $model->table();
        
        if ( ! $lastId) {
            if (isset($data[$this->_primaryKey])) {
                $lastId = $data[$this->_primaryKey];
            } else {
                $lastId = $model->getLastId();
            }
        }

        if ($lastId) {
            $data = $model->selectOne(array($this->_primaryKey => $lastId));
        }

        $this->_addLog($model->getDatabaseName(), $model->table(), self::OP_TYPE_INSERT, $lastId, $data); 
    }

    public function beforeUpdate ($model, &$where, $data) 
    {
        $model->setReadOnMaster(TRUE); 

        $originRows = $model->select($where);

        if (empty($originRows)) {
            return;
        }

        $this->_cache[md5(serialize($where))] = $originRows;
    }

    public function afterUpdate ($model, $where, $data) 
    {
        $cacheKey = md5(serialize($where));
        if ( ! isset($this->_cache[$cacheKey])) {
            return;
        }

        $originRows = $this->_cache[$cacheKey];
        unset($this->_cache[$cacheKey]);
        
        array_change_key($originRows, $this->_primaryKey);
        $ids = array_keys($originRows);
        $rows = $model->select(array($this->_primaryKey => $ids));
        $table = $model->table(); 
        $dbName = $model->getDatabaseName();

        foreach ($rows as $row) {
            $id = $row[$this->_primaryKey];
            if (isset($originRows[$id])) {
                $this->_addLog($dbName, $table, self::OP_TYPE_UPDATE, $id, $originRows[$id], $row);
            }
        }
    }

    //data 中必须有id
    public function beforeInsertReplace ($model, &$data, &$replace) 
    {
        if (!isset($data['id']) || empty($data['id'])) {
           return;
        }
        $model->setReadOnMaster(TRUE); 
        $where = array('id' => $data['id']);
        $originRows = $model->select($where);
        if (empty($originRows)) {
            return;
        }

        $this->_cache[md5(serialize($where))] = $originRows;
    }

    //$ret == 1 表示 insert， == 2 表示update
    public function afterInsertReplace ($model, $data, $replace, $ret) 
    {
        if ( (!isset($data['id']) || empty($data['id'])) && $ret == 2 ) {
           log_message('insertReplace update without id, data:' . json_encode($data), LOG_ERR);
        }
        $model->setReadOnMaster(TRUE);
        $table = $model->table(); 
        $dbName = $model->getDatabaseName();
        if ($ret == 2) {
            //var_dump($data);
            $where = array($this->_primaryKey => $data[$this->_primaryKey]);
            $cacheKey = md5(serialize($where));
            if ( ! isset($this->_cache[$cacheKey])) {
                return;
            }

            $originRows = $this->_cache[$cacheKey];
            unset($this->_cache[$cacheKey]);
            
            array_change_key($originRows, $this->_primaryKey);
            $ids = array_keys($originRows);
            //var_dump($ids);
            $rows = $model->get($ids);
           

            foreach ($rows as $row) {
                $id = $row[$this->_primaryKey];
                if (isset($originRows[$id])) {
                    $this->_addLog($dbName, $table, self::OP_TYPE_UPDATE, $id, $originRows[$id], $row);
                }
            }
        }
        else {
            $lastId = $model->getLastId();
            if ($lastId) {
                $data = $model->selectOne(array($this->_primaryKey => $lastId));
            }

            $this->_addLog($dbName, $table, self::OP_TYPE_INSERT, $lastId, $data); 
        }
    }

    public function beforeDelete ($model, &$where) 
    {
        $model->setReadOnMaster(TRUE); 

        $originRows = $model->select($where);

        if (empty($originRows)) {
            return;
        }

        $this->_cache[md5(serialize($where))] = $originRows;
    }

    public function afterDelete ($model, $where) 
    {
        $cacheKey = md5(serialize($where));
        if ( ! isset($this->_cache[$cacheKey])) {
            return;
        }

        $originRows = $this->_cache[$cacheKey];
        unset($this->_cache[$cacheKey]);
        $table = $model->table(); 
        $dbName = $model->getDatabaseName();

        foreach ($originRows as $originRow) {
            $this->_addLog($dbName, $table, self::OP_TYPE_DELETE, $originRow[$this->_primaryKey], $originRow);
        }
    }
}
