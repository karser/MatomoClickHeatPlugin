<?php


namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Container\StaticContainer;

class RedisLogger extends AbstractLogger
{
    /**
     * @var array
     */
    protected $config = [
        'redis' => [
            'scheme' => 'tcp',
            'host'   => '10.0.0.1',
            'port'   => 6379,
        ]
    ];

    /**
     * RedisLogger constructor.
     *
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        parent::__construct($configs);
        $this->model = StaticContainer::get('Piwik\Plugins\ClickHeat\Model\RedisModel');
        $this->model->createClient($this->getConfig('redis'));
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
     * @return mixed
     */
    public function getAdapterClass()
    {
        return "";
    }

    /**
     * @param $requestGroup
     *
     * @return mixed
     */
    public function getGroupUrl($requestGroup)
    {
        // TODO: Implement getGroupUrl() method.
    }

}