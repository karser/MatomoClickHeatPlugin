<?php


namespace Piwik\Plugins\ClickHeat\Adapter;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\ClickHeat\Model\MysqlModel;
use Piwik\Plugins\ClickHeat\Utils\AbstractHeatmap;

class MysqlHeatmapAdapter extends AbstractHeatmap
{
    /**
     * Maximum number of results returned by each request call
     * @var int
     */
    var $limit = 1000;

    /**
     * @var MysqlModel
     */
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = StaticContainer::get('Piwik\Plugins\ClickHeat\Model\MysqlModel');
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function startDrawing()
    {
        if (!$this->target->getGroupId()) {
            throw new \Exception("Please set target first with correct `group_id` provided.");
        }
//        $this->maxY = $this->model->getDrawingMaxY($this->target);

        return true;
    }

    /**
     * {@inheritdoc}
     * @return boolean Success
     */
    function drawPixels($image)
    {
        $limit = 0;
        do {
            /** Select with limit */
            $minY = $image * $this->height;
            $maxY = ($image + 1) * $this->height - 1;
            $results = $this->model->fetchData(
                $minY,
                $maxY,
                $this->target,
                $limit
            );
            $count = count($results);
            foreach ($results as $click) {
                $x = (int) $click['pos_x'];
                $y = (int) ($click['pos_y'] - $image * $this->height);
                if ($x < 0 || $x >= $this->width) {
                    continue;
                }
                /** Apply a calculus for the step, with increases the speed of rendering : step = 3, then pixel is drawn at x = 2 (center of a 3x3 square) */
                $x -= $x % $this->step - $this->startStep;
                $y -= $y % $this->step - $this->startStep;
                /** Add 1 to the current color of this pixel (color which represents the sum of clicks on this pixel) */
                $color = imagecolorat($this->image, $x, $y) + 1;
                imagesetpixel($this->image, $x, $y, $color);
                $this->maxClicks = max($this->maxClicks, $color);
                if ($image === 0) {
                    /** Looking for the maximum height of click */
                    $this->maxY = max($y, $this->maxY);
                }
            }
            $limit += $this->limit;
        } while ($count === $this->limit);

        return true;
    }

    /**
     * Do some cleaning or ending tasks (close database, reset array...)
     */
    public function finishDrawing()
    {
        $this->target = null;
        return true;
    }


    /**
     * @param $idSite
     *
     * @return mixed
     */
    public function getGroups($idSite)
    {
        return $this->model->getGroupsBySite($idSite);
    }

    /**
     * {@inheritdoc}
     **/
    public function getGroupUrl($requestGroup)
    {
        $group = $this->model->getGroup($requestGroup);
        if (!$group) {
            return false;
        }

        return $group['url'];
    }
}