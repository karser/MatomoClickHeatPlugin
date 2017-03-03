<?php
namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Plugins\ClickHeat\Utils\Configurable;

abstract class AbstractLogger
{
    use Configurable;

    public function __construct(array $configs)
    {
        $this->initConfig($configs);
    }

    /**
     * @param $siteId
     * @param $groupName
     * @param $referrer
     * @param $browser
     * @param $screenSize
     * @param $posX
     * @param $posY
     *
     * @return bool
     * @internal param $group
     */
    abstract public function log($siteId, $groupName, $referrer, $browser, $screenSize, $posX, $posY);

    /**
     *
     * @return mixed
     */
    abstract public function clean();

    /**
     * @return mixed
     */
    abstract public function getAdapterClass();

    /**
     * @param $requestGroup
     *
     * @return mixed
     */
    abstract public function getGroupUrl($requestGroup);
}