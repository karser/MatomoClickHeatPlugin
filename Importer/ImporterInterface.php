<?php


namespace Piwik\Plugins\ClickHeat\Importer;


interface ImporterInterface
{
    public function getImportedGroup($siteId, $groupHash);

    public function createImportedGroup($siteId, $groupHash, $groupInfo);

    public function importLog($siteId, $groupId, $log);
}