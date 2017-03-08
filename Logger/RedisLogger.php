<?php


namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Container\StaticContainer;
use Piwik\Plugins\ClickHeat\Config;

class RedisLogger extends AbstractLogger implements LoggerImporterInterface
{

    /**
     * RedisLogger constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs = [])
    {
        parent::__construct($configs);
        $this->model = StaticContainer::get('Piwik\Plugins\ClickHeat\Model\RedisModel');
        $this->model->createClient(Config::get('redis'));
    }

    /**
     * {@inheritdoc}
     */
    public function log($siteId, $groupName, $referrer, $browser, $screenSize, $posX, $posY)
    {
        $group = $this->model->getGroupByName($groupName, $siteId);
        if (!$group) {
            // create a new group
            $this->model->createGroup($groupName, $referrer, $siteId);
        }
        $groupId = $groupName;
        $newId = $this->model->addLog(
            $siteId,
            $groupId,
            $browser,
            $screenSize,
            $posX,
            $posY
        );

        return boolval($newId);
    }

    /**
     *
     * @return mixed
     */
    public function clean()
    {
        // TODO: Implement clean() method.
    }

    /**
     * @param $siteID
     *
     * @return array
     */
    public function getGroupsBySite($siteID)
    {
        $groups = $this->model->getGroups($siteID);

        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function removeLog($siteId, $groupHash, $hashField)
    {
        return $this->model->removeLoggingData($siteId, $groupHash, $hashField);
    }

    /**
     * @param $siteId
     * @param $group
     *
     * @return mixed
     */
    public function getLoggingData($siteId, $group)
    {
        return $this->model->getData($siteId, $group);
    }
}