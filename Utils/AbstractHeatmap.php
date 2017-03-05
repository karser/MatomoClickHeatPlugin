<?php

namespace Piwik\Plugins\ClickHeat\Utils;

use Piwik\Plugins\ClickHeat\Adapter\HeatmapAdapterInterface;

abstract class AbstractHeatmap extends \Heatmap implements HeatmapAdapterInterface
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

    /**
     * should return true to continue the process, otherwise it will stop
     * @return boolean
     */
    abstract public function startDrawing();

    /**
     * Find pixels coords and draw these on the current image
     * should return true to continue the process, otherwise it will stop
     *
     * @param integer $image Number of the image (to be used with $this->height)
     *
     * @return boolean
     */
    abstract public function drawPixels($image);

    /**
     * should return true to continue the process, otherwise it will stop
     * @return boolean
     */
    abstract public function finishDrawing();
}