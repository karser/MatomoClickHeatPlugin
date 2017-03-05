<?php


namespace Piwik\Plugins\ClickHeat\Adapter;


use Piwik\Container\StaticContainer;
use Piwik\Exception\ErrorException;
use Piwik\Plugins\ClickHeat\Logger\AbstractLogger;
use Piwik\Plugins\ClickHeat\Utils\AbstractHeatmap;

class HeatMapAdapterFactory
{
    /**
     *
     * @param $adapterClass
     *
     * @return null|AbstractHeatmap
     * @throws ErrorException
     */
    public static function create($adapterClass)
    {
        $adapter = null;
        $adapter = StaticContainer::get($adapterClass);
        if (!$adapter instanceof AbstractHeatmap) {
            $error = sprintf('Adapter class must be an instance of Piwik\Plugins\ClickHeat\Utils\AbstractHeatmap . %s given.', $adapterClass);
            throw new ErrorException($error, 500);
        }
        return $adapter;
    }
}