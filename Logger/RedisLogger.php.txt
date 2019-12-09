<?php


namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Container\StaticContainer;
use Piwik\Plugin\PluginException;
use Piwik\Plugins\ClickHeat\ClickHeat;
use Piwik\Plugins\ClickHeat\Config;
use Predis\Client;
use Predis\PredisException;

class RedisLogger extends AbstractLogger implements LoggerImporterInterface
{

    /**
     * RedisLogger constructor.
     *
     * @param array $configs
     *
     * @throws PluginException
     */
    public function __construct(array $configs = [])
    {
        parent::__construct($configs);
        $this->model = StaticContainer::get('Piwik\Plugins\ClickHeat\Model\RedisModel');
        try {
            $redisClient = $this->createClient(Config::get('redis'));
        } catch (\Exception $e) {
            throw new PluginException(
                ClickHeat::getPluginNameFromNamespace(ClickHeat::class),
                "Cannot initialize Redis connection with given configurations. Please check your settings"
            );
        }
        $this->model->setClient($redisClient);
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

    /**
     * @param $redisConfig
     *
     * @return Client
     */
    private function createClient($redisConfig)
    {
        $options = $parameters = [];
        if (!is_null($redisConfig['database'])) {
            $options['parameters']['database'] = $redisConfig['database'];
        }
        if (!is_null($redisConfig['password'])) {
            $options['parameters']['password'] = $redisConfig['password'];
        }
        if ($redisConfig['sentinel']) {
            $options['replication'] = 'sentinel';
            $options['service'] = $redisConfig['sentinel'];
        }
        $parameters = $this->buildRedisHostParameters(explode(',', $redisConfig['host']), explode(',', $redisConfig['port']));
        $client = new Client($parameters, $options);

        return $client;
    }

    /**
     * @param $hosts
     * @param $ports
     *
     * @return array
     */
    private function buildRedisHostParameters(array $hosts, array $ports)
    {
        $parameters = [];
        foreach ($hosts as $key => $host) {
            $parameters[] = sprintf("tcp://%s:%s", $host, $ports[$key]);
        }

        return $parameters;
    }
}