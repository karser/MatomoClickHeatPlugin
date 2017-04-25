<?php

namespace Piwik\Plugins\ClickHeat;

use Piwik\Plugins\ClickHeat\Utils\Configurable;

class Config
{
    use Configurable;

    /**
     * Config constructor.
     */
    public static function init()
    {
        $settings = new SystemSettings();
        self::mergeSystemSettings($settings);
        self::$config = self::$configurations;
        self::set('logPath', PIWIK_INCLUDE_PATH . self::get('logPath'));
        self::set('cachePath', PIWIK_INCLUDE_PATH . self::get('cachePath'));
        //        TODO: refactor configuration
    }

    static $configurations = [
        // will be override by system settings
        'checkReferrer'  => false,
        'redis'          => [
            'sentinel' => '',
            'password' => '',
            'database' => 0,
            'host'     => 'localhost',
            'port'     => '6379',
        ],

        // TODO: below configurations need to be refactored
        'logger'         => 'Piwik\Plugins\ClickHeat\Logger\RedisLogger',
        'adapter'        => 'Piwik\Plugins\ClickHeat\Adapter\MysqlHeatmapAdapter',
        'logPath'        => '/tmp/cache/clickheat/logs/',
        'cachePath'      => '/tmp/cache/clickheat/cache/',
        'fileSize'       => 0,
        'memory'         => 0,
        'timeout'        => 180,
        'step'           => 5,
        'dot'            => 19,
        'flush'          => 40, //days
        'start'          => 'm',
        'palette'        => false,
        'heatmap'        => true,
        'hideIframes'    => true,
        'hideFlashes'    => true,
        'yesterday'      => false,
        'alpha'          => 80,
        'version'        => '0.1.9',
        '__screenSizes'  => [0/** Must start with 0 */, 640, 800, 1024, 1280, 1440, 1600, 1800],
        '__browsersList' => ['all' => '', 'firefox' => 'Firefox', 'chrome' => 'Google Chrome', 'msie' => 'Internet Explorer', 'safari' => 'Safari', 'opera' => 'Opera', 'kmeleon' => 'K-meleon', 'unknown' => ''],
    ];

    /**
     * @param SystemSettings $settings
     */
    private static function mergeSystemSettings(SystemSettings $settings)
    {
        $sentinelMaster = $settings->useSentinelBackend->getValue() ? $settings->sentinelMasterName->getValue() : null;
        $redisConfigs = [
            'sentinel' => $sentinelMaster,
            'database' => $settings->redisDatabase->getValue(),
            'password' => $settings->redisPassword->getValue() ? $settings->redisPassword->getValue() : null,
            'host'     => $settings->redisHost->getValue(),
            'port'     => $settings->redisPort->getValue(),
        ];
        self::$configurations['redis'] = $redisConfigs;
        self::$configurations['checkReferrer'] = $settings->checkReferrer->getValue();
        self::$configurations['memory'] = $settings->memory->getValue();
        self::$configurations['timeout'] = $settings->timeout->getValue();
    }
}
