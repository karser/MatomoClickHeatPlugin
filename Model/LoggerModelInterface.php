<?php


namespace Piwik\Plugins\ClickHeat\Model;


interface LoggerModelInterface
{
    /**
     * Add click heat log
     *
     * @param        $siteId
     * @param        $groupId
     * @param        $browser
     * @param        $screenSize
     * @param        $posX
     * @param        $posY
     *
     * @param string $date
     *
     * @return int
     */
    public function addLog($siteId, $groupId, $browser, $screenSize, $posX, $posY, $date = '');

    /**
     * @param $days
     *
     * @return mixed
     */
    public function cleanLogging($days);

    /**
     * @param $groupName
     * @param $siteId
     *
     * @return mixed
     */
    public function getGroupByName($groupName, $siteId);

    /**
     * @param $groupName
     * @param $url
     * @param $siteId
     *
     * @return mixed
     */
    public function createGroup($groupName, $url, $siteId);
}