<?php


namespace Piwik\Plugins\ClickHeat\Adapter;


use Piwik\Container\StaticContainer;
use Piwik\Plugins\ClickHeat\Logger\AbstractLogger;
use Piwik\Plugins\ClickHeat\Utils\ImprovedHeatmap;

class HeatMapAdapterFactory
{
    /**
     * @param AbstractLogger $logger
     *
     * @return ImprovedHeatmap|null
     */
    public static function create(AbstractLogger $logger)
    {
        $adapterClass = $logger->getAdapterClass();
        $adapter = null;
        switch ($adapterClass) {
            case 'Piwik\Plugins\ClickHeat\Adapter\MysqlHeatmapAdapter':
                $adapter = StaticContainer::get($adapterClass);
                break;
        }

        return $adapter;
    }
}