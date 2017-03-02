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
     * @param $group
     * @param $browser
     * @param $screenSize
     * @param $posX
     * @param $posY
     *
     * @return boolean
     */
    abstract public function log($siteId, $group, $browser, $screenSize, $posX, $posY);

    /**
     *
     * @return mixed
     */
    abstract public function clean();
}