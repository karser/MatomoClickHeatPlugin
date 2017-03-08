<?php
namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Plugins\ClickHeat\Model\LoggerModelInterface;
use Piwik\Plugins\ClickHeat\Utils\Configurable;

abstract class AbstractLogger
{
    use Configurable;

    /**
     * @var LoggerModelInterface
     */
    protected $model;

    /**
     * AbstractLogger constructor.
     *
     * @param array $configs
     */
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
     */
    abstract public function log($siteId, $groupName, $referrer, $browser, $screenSize, $posX, $posY);

    /**
     *
     * @return mixed
     */
    abstract public function clean();

}