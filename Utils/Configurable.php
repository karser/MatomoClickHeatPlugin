<?php

namespace Piwik\Plugins\ClickHeat\Utils;


trait Configurable
{
    public static $config = [];

    /**
     * @param array $configs
     *
     * @return bool
     */
    public static function initConfig(array $configs)
    {
        if (!count(self::$config) || !count($configs)) {
            return false;
        }
        foreach (self::$config as $configKey => $value) {
            if (isset($configs[$configKey])) {
                self::$config[$configKey] = $configs[$configKey];
            }
        }

        return true;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public static function set($name, $value)
    {
        if ($name) {
            self::$config[$name] = $value;
        } else {
            self::$config = $value;
        }
    }

    /**
     * @param string $name
     *
     * @param null   $default
     *
     * @return mixed
     */
    public static function get($name = '', $default = null)
    {
        if ($name) {
            return isset(self::$config[$name]) ? self::$config[$name] : $default;
        }
        return self::$config;
    }

    /**
     * @return mixed
     */
    public static function all()
    {
        return self::get();
    }
}
