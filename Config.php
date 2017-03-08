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
        self::$config = self::$configurations;
        self::set('logPath', PIWIK_INCLUDE_PATH . self::get('logPath'));
        self::set('cachePath', PIWIK_INCLUDE_PATH . self::get('cachePath'));
//        TODO: refactor configuration
    }

     static $configurations = [
        'redis'          => "tcp://redis:6379", // URI string
        'logger'         => 'Piwik\Plugins\ClickHeat\Logger\RedisLogger',
        'adapter'        => 'Piwik\Plugins\ClickHeat\Adapter\MysqlHeatmapAdapter',
        'logPath'        => '/tmp/cache/clickheat/logs/',
        'cachePath'      => '/tmp/cache/clickheat/cache/',
        'referers'       => true,
        'fileSize'       => 0,
        'adminLogin'     => '',
        'adminPass'      => '',
        'viewerLogin'    => '',
        'viewerPass'     => '',
        'memory'         => 50,
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
        '__browsersList' => ['all' => '', 'firefox' => 'Firefox', 'chrome' => 'Google Chrome', 'msie' => 'Internet Explorer', 'safari' => 'Safari', 'opera' => 'Opera', 'kmeleon' => 'K-meleon', 'unknown' => '']
    ];
}
