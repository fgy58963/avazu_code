<?php
/**
 * farm db对象，用于操作拆库DB
 *
 */
class Db_FarmDb extends Db_Base
{
    protected static $_farmDbs;

    protected function __construct($farmConfig)
    {
        parent::__construct(
            $farmConfig['dbName'], $farmConfig['dbUser'], $farmConfig['dbPwd'],
            $farmConfig['read'],
            $farmConfig['write']
        );
    }

    public static function & getInstanceByFarmId($farmId, $clusterId)
    {
        if (empty(self::$_farmDbs[$clusterId][$farmId])) {
            $farmAgent = new Db_FarmAgent($clusterId);
            $farmConfig = $farmAgent->getFarmInfoByFarmId($farmId);

            if (empty($farmConfig)) {
                return NULL;
            }

            $db = new self($farmConfig);
            self::$_farmDbs[$clusterId][$farmId] = &$db;
        }

        return self::$_farmDbs[$clusterId][$farmId];
    }

    public static function & getInstanceByObjectId($objectId, $clusterId)
    {
        $farmAgent = new Db_FarmAgent($clusterId);
        $farmId = $farmAgent->getFarmId($objectId);
        return self::getInstanceByFarmId($farmId, $clusterId);
    }

    public static function closeAll()
    {
        foreach (self::$_farmDbs as $clusterId => &$farmDbs) {
            foreach ($farmDbs as $farmId => &$farmDb) {
                $farmDb->close();
            }
        }
    }
    
    public static function clearAll()
    {
        foreach (self::$_farmDbs as $clusterId => &$farmDbs) {
            foreach ($farmDbs as $farmId => &$farmDb) {
                $farmDb = NULL;
            }
        }
    }
}

