<?php
$clickHeatConfig = [
    'redis'          => [
        'scheme' => 'tcp',
        'host'   => 'redis',
        'port'   => 6379,
    ],
    'logger'         => 'Piwik\Plugins\ClickHeat\Logger\RedisLogger',
    'adapter'        => 'Piwik\Plugins\ClickHeat\Adapter\MysqlHeatmapAdapter',
    'logPath'        => PIWIK_INCLUDE_PATH . '/tmp/cache/clickheat/logs/',
    'cachePath'      => PIWIK_INCLUDE_PATH . '/tmp/cache/clickheat/cache/',
    'referers'       => true,
    'groups'         => true,
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