<?php
/**
 * 数据库表操作前进行字段长度检测
 */
class Db_Model_EventHandler_FieldView extends Db_Model_EventHandler
{
	public function beforeInsert ($model, &$data) {
		$sql = 'select * from '.$model->getTable();
		echo $sql;
		print_r($model);
		$query = $model->execute($sql);
		print_r($query);exit;
		//$aa=$query->fetchColumn(0);
		//var_dump($aa);
		$bb=$query->columnCount();
		var_dump($bb);exit;
	}
}