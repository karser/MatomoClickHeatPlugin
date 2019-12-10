<?php
/**
 * Created by PhpStorm.
 * User: Trung Tran
 * Date: 2/28/17
 * Time: 16:43
 */

namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Container\StaticContainer;
use Piwik\Plugins\ClickHeat\Model\MysqlModel;

class MysqlLogger extends AbstractLogger
{
    public static $config = [
        'flush'  => 0
    ];
    /**
     * MysqlLogger constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        parent::__construct($configs);
        $this->model = StaticContainer::get(MysqlModel::class);
    }

    /**
     * {@inheritdoc}
     */
    public function log($siteId, $groupName, $referrer, $browser, $screenSize, $posX, $posY)
    {
        $group = $this->model->getGroupByName($groupName, $siteId);
        if (!$group) {
            // create a new group
            $group['id'] = $this->model->createGroup($groupName, $siteId, $referrer);
        }
        $newId = $this->model->addLog(
            $siteId,
            $group['id'],
            $browser,
            $screenSize,
            $posX,
            $posY
        );

        return (bool)$newId;
    }

    public function clean()
    {
        $this->model->cleanLogging(self::get('flush'));
    }

}