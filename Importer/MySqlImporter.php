<?php


namespace Piwik\Plugins\ClickHeat\Importer;


use Piwik\Plugins\ClickHeat\Model\MysqlModel;

class MySqlImporter extends MysqlModel implements ImporterInterface
{

    public function importLog($siteId, $groupId, $log)
    {
        $data = [
            'group_id'    => $groupId,
            'pos_x'       => $log['pos_x'],
            'pos_y'       => $log['pos_y'],
            'date'        => $log['date'],
            'browser'     => $log['browser'],
            'screen_size' => $log['screen_size'],
        ];
        if ($this->getDb()->insert($this->table, $data)) {
            return $this->getDb()->lastInsertId();
        }

        return false;
    }

    public function getImportedGroup($siteId, $groupHash)
    {
        $query = "SELECT * FROM `{$this->groupTable}` WHERE `import_hash` = ? AND `idsite` = ?";

        return $this->getDb()->fetchRow($query, [$groupHash, $siteId]);
    }

    public function createImportedGroup($siteId, $groupHash, $groupInfo)
    {
        $data = [
            'name'        => $groupInfo['name'],
            'url'         => $groupInfo['url'],
            'idsite'      => $siteId,
            'import_hash' => $groupHash,
        ];
        if ($this->getDb()->insert($this->groupTable, $data)) {
            return $this->getDb()->lastInsertId();
        }

        return false;
    }
}