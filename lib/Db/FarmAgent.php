<?php

/**
 * farm配置及策略，could be refactored into single instance pattern
 *
 *
 */
class Db_FarmAgent
{
    private $_clusterId;

    const FARM_ID_LEN = 3;
 
    public function __construct($clusterId)
    {
        $this->_clusterId = $clusterId;
    }
    
    /**
     * 根据object id获取相关数据库的相关信息
     * 
     * @param integer $objectId
     * @return array
     */
    public function getFarmInfo($objectId)
    {
        $farmId = $this->getFarmId($objectId);
        return $this->getFarmInfoByFarmId($farmId);
    }

    /**
     * 根据farm id 
     * @param integer $farmId
     * @return array|boolean 当farm id 不存在或cluster不存在时返回FALSE
     */
    public function getFarmInfoByFarmId($farmId)
    {
        if (empty($farmId)) {
            //Error
            return FALSE;
        }
        
        $clusterId = $this->_clusterId;

        $clustersConfig = Config::get('db_cluster');
        $phyConfig = Config::get('db_physical');
        $clusterConfig = $clustersConfig[$clusterId];

        if (empty($clusterConfig)) {
            return FALSE;
        }

        if (isset($clusterConfig['map'][$farmId])) {
            $phyShardId = $clusterConfig['map'][$farmId];
        } elseif (!empty($clusterConfig['map']['rule'])) {
            $ruleFun = $clusterConfig['map']['rule'];
            $phyShardId = call_user_func($ruleFun, $farmId); 
        } else {
            $phyShardId = $clusterConfig['map'][0];
        }
        
        $suffix = str_pad($farmId, self::FARM_ID_LEN, '0', STR_PAD_LEFT);
        if (isset($clusterConfig['farm_id_converter'])) {
            $converter = $clusterConfig['farm_id_converter'];
            $suffix = call_user_func($converter, $farmId);
        }
        
        $dbName = $clusterConfig['db_name_prefix'] . $suffix;
        $phyFarmInfo = $phyConfig[$phyShardId];
        
        $dbUser = isset($phyFarmInfo['db_user']) ? 
            $phyFarmInfo['db_user'] : $phyConfig['db_user'];
        $dbPwd = isset($phyFarmInfo['db_pwd']) ?
            $phyFarmInfo['db_pwd'] : $phyConfig['db_pwd'];
            
        return array(
            'read'  => $phyFarmInfo['read'],
            'write' => $phyFarmInfo['write'],
            'dbName'    => $dbName,
            'dbUser'    => $dbUser,
            'dbPwd'     => $dbPwd,
        );
    }
    
    /**
     * 判断两个object是否在同一个farm中
     *
     * @param integer $objectIdA
     * @param integer $objectIdB
     * @return boolean
     */
    public function isInSameFarm($objectIdA, $objectIdB)
    {
        return $this->getFarmId($objectIdA) === $this->getFarmId($objectIdB);
    }

    public function getFarmIdOfNewObject()
    {
        
    }

    /**
     * 获取对象所在farm
     *
     * @param integer objectId
     * @return integer farm id
     */
    public function getFarmId($objectId)
    {
        if ($objectId === '' || $objectId === FALSE || $objectId === NULL) {
            return FALSE;
        }

        $clustersConfig = Config::get('db_cluster');
        if (!empty($clustersConfig[$this->_clusterId]['farm_policy'])) {
            return call_user_func(
                $clustersConfig[$this->_clusterId]['farm_policy'],
                $objectId
            );
        }

        return FALSE;
    }

    /**
     * 返回一个cluster的所有farm id
     *
     * @return array() farm id arry
     */
    public function getAllFarmIds()
    {
        $clustersConfig = Config::get('db_cluster');
        if (!empty($clustersConfig[$this->_clusterId]['farm_count'])) {
            return range(1, $clustersConfig[$this->_clusterId]['farm_count']);
        }

        return FALSE;
    }

}
