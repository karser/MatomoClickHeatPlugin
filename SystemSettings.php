<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * forked from https://github.com/piwik/plugin-QueuedTracking/blob/master/SystemSettings.php for Redis configuration
 */
namespace Piwik\Plugins\ClickHeat;

use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\Piwik;
use Exception;

/**
 * Defines Settings for QueuedTracking.
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $redisHost;

    /** @var Setting */
    public $redisPort;

    /** @var Setting */
    public $redisTimeout;

    /** @var Setting */
    public $redisDatabase;

    /** @var Setting */
    public $redisPassword;

    /** @var Setting */
    public $useSentinelBackend;

    /** @var Setting */
    public $sentinelMasterName;

    /**
     * @var Setting
     */
    public $checkReferrer;

    protected function init()
    {
        $this->checkReferrer = $this->createCheckReferrer();
        $this->useSentinelBackend = $this->createUseSentinelBackend();
        $this->sentinelMasterName = $this->createSetSentinelMasterName();
        $this->redisHost = $this->createRedisHostSetting();
        $this->redisPort = $this->createRedisPortSetting();
        $this->redisTimeout = $this->createRedisTimeoutSetting();
        $this->redisDatabase = $this->createRedisDatabaseSetting();
        $this->redisPassword = $this->createRedisPasswordSetting();
    }

    public function isUsingSentinelBackend()
    {
        return $this->useSentinelBackend->getValue();
    }

    public function getSentinelMasterName()
    {
        return $this->sentinelMasterName->getValue();
    }

    private function createRedisHostSetting()
    {
        $self = $this;

        return $this->makeSetting('redisHost', $default = '127.0.0.1', FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($self) {
            $field->title = 'Redis host';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 500);
            $field->inlineHelp = 'Remote host of the Redis server. Max 500 characters are allowed.';

            if ($self->isUsingSentinelBackend()) {
                $field->inlineHelp .= $self->getInlineHelpSentinelMultipleServers('hosts');
            }

            $field->validate = function ($value) use ($self) {
                $self->checkMultipleServersOnlyConfiguredWhenSentinelIsEnabled($value);

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
        if ($this->isUsingSentinelBackend()) {
            $default = '26379';
        }

        return $this->makeSetting('redisPort', $default, FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($self) {
            $field->title = 'Redis port';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 100);
            $field->inlineHelp = 'Port the Redis server is running on. Value should be between 1 and 65535';

            if ($self->isUsingSentinelBackend()) {
                $field->inlineHelp .= $self->getInlineHelpSentinelMultipleServers('ports');
            }

            $field->validate = function ($value) use ($self) {
                $self->checkMultipleServersOnlyConfiguredWhenSentinelIsEnabled($value);
                $ports = $self->convertCommaSeparatedValueToArray($value);

                foreach ($ports as $port) {
                    if (!is_numeric($port)) {
                        throw new \Exception('A port has to be a number');
                    }

                    $port = (int) $port;

                    if ($port < 1) {
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
            $field->uiControlAttributes = array('size' => 5);
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

    private function createRedisPasswordSetting()
    {
        return $this->makeSetting('redisPassword', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Redis password';
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->uiControlAttributes = array('size' => 100);
            $field->inlineHelp = 'Password set on the Redis server, if any. Redis can be instructed to require a password before allowing clients to execute commands.';
            $field->validate = function ($value) {
                if (strlen($value) > 100) {
                    throw new \Exception('Max 100 characters allowed');
                }
            };
        });
    }

    private function createRedisDatabaseSetting()
    {
        return $this->makeSetting('redisDatabase', $default = 0, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = 'Redis database';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 5);
            $field->inlineHelp = 'In case you are using Redis for caching make sure to use a different database.';
            $field->validate = function ($value) {
                if (!is_numeric($value) || false !== strpos($value, '.')) {
                    throw new \Exception('The database has to be an integer');
                }

                if (strlen($value) > 5) {
                    throw new \Exception('Max 5 digits allowed');
                }
            };
        });
    }

    public function getInlineHelpSentinelMultipleServers($nameOfSetting)
    {
        return 'As you are using Redis Sentinel, you can define multiple ' . $nameOfSetting . ' comma separated. Make sure to specify as many hosts as you have specified ports. For example to configure two servers "127.0.0.1:26379" and "127.0.0.2:26879" specify "127.0.0.1,127.0.0.2" as host and "26379,26879" as ports.';
    }

    public function checkMultipleServersOnlyConfiguredWhenSentinelIsEnabled($value)
    {
        if ($this->isUsingSentinelBackend()) {
            return;
        }

        $values = $this->convertCommaSeparatedValueToArray($value);

        if (count($values) > 1) {
            throw new Exception(Piwik::translate('QueuedTracking_MultipleServersOnlyConfigurableIfSentinelEnabled'));
        }
    }

    public function convertCommaSeparatedValueToArray($value)
    {
        if ($value === '' || $value === false || $value === null) {
            return array();
        }

        $values = explode(',', $value);
        $values = array_map('trim', $values);

        return $values;
    }

    private function createUseSentinelBackend()
    {
        return $this->makeSetting('useSentinelBackend', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = 'Enable Redis Sentinel\'';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->uiControlAttributes = array('size' => 3);
            $field->inlineHelp = 'If enabled, the Redis Sentinel feature will be used. Make sure to update host and port if needed. Once you have enabled and saved the change, you will be able to specify multiple hosts and ports comma separated.';
        });
    }

    private function createSetSentinelMasterName()
    {
        return $this->makeSetting('sentinelMasterName', $default = 'mymaster', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Redis Sentinel Master name';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->uiControlAttributes = array('size' => 200);
            $field->inlineHelp = 'The sentinel master name only needs to be configured if Sentinel is enabled.';
            $field->validate = function ($value) {
                if (!empty($value) && strlen($value) > 200) {
                    throw new \Exception('Max 200 characters are allowed');
                }
            };
            $field->transform = function ($value) {
                if (empty($value)) {
                    return '';
                }
                return trim($value);
            };
        });
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

    private function createCheckReferrer()
    {
        return $this->makeSetting('checkReferrer', $default = true, FieldConfig::TYPE_BOOL, function (FieldConfig
                                                                                                    $field) {
            $field->title = 'Enable referrer URL checking\'';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->uiControlAttributes = array('size' => 3);
            $field->inlineHelp = 'If enabled, only requests to tracker that have referrer in header and match site\'s hostname are accepted.';
        });
    }

}
