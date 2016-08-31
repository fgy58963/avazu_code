<?php
class Db_Model_EventHandler
{
    public function beforeInsert ($model, &$data) {}

    public function afterInsert ($model, $data, $lastId) {}

    public function beforeUpdate ($model, &$where, $data) {}

    public function afterUpdate ($model, $where, $data) {}

    public function beforeInsertReplace ($model, &$data, &$replace) {}

    public function afterInsertReplace ($model, $data, $replace) {}

    public function beforeDelete ($model, &$where) {}

    public function afterDelete ($model, $where) {}
}
