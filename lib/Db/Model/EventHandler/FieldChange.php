<?php
/**
 * 关注某些字段值的更新
 */
abstract class Db_Model_EventHandler_FieldChange extends Db_Model_EventHandler
{
    protected $_fields = array();
    protected $_cache = array();
    protected $_primaryKey = 'id';

    public function __construct($fields = array(), $primaryKey = 'id')
    {
        $this->_fields = $fields;
        $this->_primaryKey = $primaryKey;
    }

    public function beforeUpdate ($model, $where, $data) 
    {
        $keys = array_keys($data);

        if ( ! array_intersect(array_keys($data), $this->_fields)) {
            return;
        }

        $model->setReadOnMaster(TRUE);

        $rows = $model->select($where);

        if ( ! $rows) {
            return;
        }

        $this->_cache[md5(json_encode($where))] = $rows;
    }

    public function afterUpdate ($model, $where, $data)
    {
        $cacheKey = md5(json_encode($where));

        if ( ! isset($this->_cache[$cacheKey])) {
            return;
        }

        $originRows = $this->_cache[$cacheKey];
        unset($this->_cache[$cacheKey]);
        array_change_key($originRows, $this->_primaryKey);

        $rows = $model->select($where);
        
        foreach ($rows as $row) {
            $id = $row[$this->_primaryKey];
            if ( ! isset($originRows[$id])) {
                continue;
            }

            $originRow = $originRows[$id];

            $changedFields = array();
            foreach ($this->_fields as $field) {
                if ($originRow[$field] != $row[$field]) {
                    $changedFields[] = $field;
                }
            }
            if ($changedFields) {
                $this->onChange($originRow, $row, $changedFields);
            }
        }
    }

    abstract protected function onChange($originRow, $row, $changedFields);
}
