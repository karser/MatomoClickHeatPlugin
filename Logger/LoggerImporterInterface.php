<?php


namespace Piwik\Plugins\ClickHeat\Logger;


interface LoggerImporterInterface
{
    /**
     * @param $siteID
     *
     * @return array
     */
    public function getGroupsBySite($siteID);

    /**
     * @param $siteId
     * @param $groupName
     *
     * @param $hashField
     *
     * @return mixed
     */
    public function removeLog($siteId, $groupName, $hashField);

    /**
     * @param $siteId
     * @param $groupName
     *
     * @return mixed
     */
    public function getLoggingData($siteId, $groupName);
}