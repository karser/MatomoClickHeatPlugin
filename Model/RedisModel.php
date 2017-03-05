<?php


namespace Piwik\Plugins\ClickHeat\Model;


use Predis\Client;

class RedisModel implements LoggerModelInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @param $config
     *
     * @return Client
     */
    public function createClient($config)
    {
        $this->client = new Client($config);
    }

    /**
     * {@inheritdoc}
     */
    public function addLog($siteId, $groupId, $browser, $screenSize, $posX, $posY, $date = '')
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $data = [
            'browser'     => $browser,
            'screen_size' => $screenSize,
            'pos_x'       => $posX,
            'pos_y'       => $posY,
            'date'        => $date
        ];
        $keyCache = $this->getLogKey($siteId, $groupId);

        return $this->client->lpush($keyCache, json_encode($data));
    }

    /**
     * @param $days
     *
     * @return mixed
     */
    public function cleanLogging($days)
    {
        // TODO: Implement cleanLogging() method.
    }

    /**
     * @param $groupName
     * @param $siteId
     *
     * @return mixed
     */
    public function getGroupByName($groupName, $siteId)
    {
        $keyCache = $this->getGroupKey($siteId);
        $group = $this->client->hget($keyCache, md5($groupName));
        if ($group) {
            return json_decode($group);
        }

        return false;
    }

    /**
     * @param $siteId
     *
     * @return string
     */
    private function getGroupKey($siteId)
    {
        return "ClickHeat:Groups:{$siteId}";
    }

    /**
     * @param $siteId
     * @param $groupName
     *
     * @return string
     */
    private function getLogKey($siteId, $groupName)
    {
        return "ClickHeat:Log:{$siteId}:" . md5($groupName);
    }

    /**
     * @param $groupName
     * @param $url
     * @param $siteId
     *
     * @return mixed
     */
    public function createGroup($groupName, $url, $siteId)
    {
        $keyCache = $this->getGroupKey($siteId);
        $data = json_encode([
            'name' => $groupName,
            'url'  => $url
        ]);

        return $this->client->hset($keyCache, md5($groupName), $data);
    }

}