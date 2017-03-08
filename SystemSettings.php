<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ClickHeat;

use Exception;
use Piwik\Piwik;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;

/**
 * Defines Settings for ClickHeat.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->metric->getValue();
 * $settings->description->getValue();
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $redisHost;

    /** @var Setting */
    public $redisPort;

    /** @var Setting */
    public $redisTimeout;

    protected function init()
    {
        $this->redisHost = $this->createRedisHostSetting();
        $this->redisPort = $this->createRedisPortSetting();
        $this->redisTimeout = $this->createRedisTimeoutSetting();
    }

    public function isUsingUnixSocket()
    {
        return substr($this->redisHost->getValue(), 0, 1) === '/';
    }

    private function createRedisHostSetting()
    {
        $self = $this;

        return $this->makeSetting('redisHost', $default = '127.0.0.1', FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($self) {
            $field->title = 'Redis host or unix socket';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = ['size' => 500];
            $field->inlineHelp = 'Remote host or unix socket of the Redis server. Max 500 characters are allowed.';


            $field->validate = function ($value) use ($self) {
                if (strlen($value) > 500) {
                    throw new \Exception('Max 500 characters allowed');
                }
            };

            $field->transform = function ($value) use ($self) {
                $hosts = $self->convertCommaSeparatedValueToArray($value);

                return implode(',', $hosts);
            };
        });
    }

    private function createRedisPortSetting()
    {
        $self = $this;

        $default = '6379';

        return $this->makeSetting('redisPort', $default, FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($self) {
            $field->title = 'Redis port';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = ['size' => 100];
            $field->inlineHelp = 'Port the Redis server is running on.';


            $field->validate = function ($value) use ($self) {
                $ports = $self->convertCommaSeparatedValueToArray($value);

                foreach ($ports as $port) {
                    if (!is_numeric($port)) {
                        throw new \Exception('A port has to be a number');
                    }

                    $port = (int) $port;

                    if ($port < 1 && !$this->isUsingUnixSocket()) {
                        throw new \Exception('Port has to be at least 1');
                    }

                    if ($port >= 65535) {
                        throw new \Exception('Port should be max 65535');
                    }
                }
            };
            $field->transform = function ($value) use ($self) {
                $ports = $self->convertCommaSeparatedValueToArray($value);
                $ports = array_map('intval', $ports);

                return implode(',', $ports);
            };
        });
    }

    private function createRedisTimeoutSetting()
    {
        $setting = $this->makeSetting('redisTimeout', $default = 0.0, FieldConfig::TYPE_FLOAT, function (FieldConfig $field) {
            $field->title = 'Redis timeout';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = ['size' => 5];
            $field->inlineHelp = 'Redis connection timeout in seconds. "0.0" meaning unlimited. Can be a float eg "2.5" for a connection timeout of 2.5 seconds.';
            $field->validate = function ($value) {

                if (!is_numeric($value)) {
                    throw new \Exception('Timeout should be numeric, eg "0.1"');
                }

                if (strlen($value) > 5) {
                    throw new \Exception('Max 5 characters allowed');
                }
            };
        });

        // we do not expose this one to the UI currently. That's on purpose
        $setting->setIsWritableByCurrentUser(false);

        return $setting;
    }

    public function convertCommaSeparatedValueToArray($value)
    {
        if ($value === '' || $value === false || $value === null) {
            return [];
        }

        $values = explode(',', $value);
        $values = array_map('trim', $values);

        return $values;
    }

    public function checkMatchHostsAndPorts()
    {
        $hosts = $this->redisHost->getValue();
        $ports = $this->redisPort->getValue();
        $numHosts = count(explode(',', $hosts));
        $numPorts = count(explode(',', $ports));

        if (($hosts || $ports) && $numHosts !== $numPorts) {
            throw new Exception(Piwik::translate('QueuedTracking_NumHostsNotMatchNumPorts'));
        }
    }

    public function save()
    {
        $this->checkMatchHostsAndPorts();
        parent::save();
    }
}
