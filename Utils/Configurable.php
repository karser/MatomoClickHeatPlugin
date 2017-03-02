<?php

namespace Piwik\Plugins\ClickHeat\Utils;


trait Configurable
{
    protected $config = [];

    /**
     * @param array $configs
     *
     * @return bool
     */
    public function initConfig(array $configs)
    {
        if (!count($this->config) || !count($configs)) {
            return false;
        }
        foreach ($this->config as $configKey) {
            if (array_key_exists($configKey, $configs)) {
                $this->config[$configKey] = $configs[$configKey];
            }
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getConfig($name = '')
    {
        if ($name) {
            return isset($this->config[$name]) ? $this->config[$name] : null;
        }

        return $this->config;
    }
}