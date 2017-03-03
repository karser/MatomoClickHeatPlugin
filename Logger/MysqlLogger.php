<?php
/**
 * Created by PhpStorm.
 * User: Trung Tran
 * Date: 2/28/17
 * Time: 16:43
 */

namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Container\StaticContainer;
use Piwik\Plugins\ClickHeat\Model;

class MysqlLogger extends AbstractLogger
{
    protected $config = [
        'flush'  => 0
    ];

    /**
     * @var Model
     */
    protected $model;

    /**
     * MysqlLogger constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        parent::initConfig($configs);
        $this->model = StaticContainer::get('Piwik\Plugins\ClickHeat\Model');
    }

    /**
     * {@inheritdoc}
     */
    public function log($siteId, $groupName, $referrer, $browser, $screenSize, $posX, $posY)
    {
        $group = $this->model->getGroupByName($groupName, $siteId);
        if (!$group) {
            // create a new group
            $group['id'] = $this->model->createGroup($groupName, $siteId);
        }
        $newId = $this->model->addLog(
            $group['id'],
            $browser,
            $screenSize,
            $posX,
            $posY
        );

        return boolval($newId);
    }

    public function clean()
    {
        $this->model->cleanLogging($this->getConfig('flush'));
    }

    /**
     * @return string
     */
    public function getAdapterClass()
    {
        return 'Piwik\Plugins\ClickHeat\Adapter\MysqlHeatmapAdapter';
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