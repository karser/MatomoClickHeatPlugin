<?php

namespace Piwik\Plugins\ClickHeat\Utils;


class ImprovedHeatmap extends \Heatmap
{
    /**
     * @var DrawingTarget
     */
    protected $target;

    /**
     * @param DrawingTarget $target
     */
    public function setTarget(DrawingTarget $target)
    {
        $this->target = $target;
    }

    public function boostrap()
    {
        /* Add files */
        for ($day = 0; $day < $days; $day++) {
            $currentDate = date('Y-m-d', mktime(0, 0, 0, date('m', $dateStamp), date('d', $dateStamp) + $day, date('Y', $dateStamp)));
            $heatmapObject->addFile(self::$conf['logPath'] . $group . '/' . $currentDate . '.log');
        }
    }
}