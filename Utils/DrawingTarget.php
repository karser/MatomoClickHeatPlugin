<?php


namespace Piwik\Plugins\ClickHeat\Utils;


class DrawingTarget
{
    /**
     * @var string
     */
    protected $groupId;

    /**
     * @var string
     */
    protected $browser;

    /**
     * @var int
     */
    protected $minScreen;

    /**
     * @var int
     */
    protected $maxScreen;

    /**
     * @var string
     */
    protected $minDate;

    /**
     * @var string
     */
    protected $maxDate;

    /**
     * @var string
     */
    protected $logPath;

    /**
     * DrawingTarget constructor.
     *
     * @param $target
     */
    public function __construct($target)
    {
        foreach ($target as $key => $value) {
            if (property_exists(self::class, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     *
     * @return DrawingTarget
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param string $browser
     *
     * @return DrawingTarget
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return int
     */
    public function getMinScreen()
    {
        return $this->minScreen;
    }

    /**
     * @param int $minScreen
     *
     * @return DrawingTarget
     */
    public function setMinScreen($minScreen)
    {
        $this->minScreen = $minScreen;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxScreen()
    {
        return $this->maxScreen;
    }

    /**
     * @param int $maxScreen
     *
     * @return DrawingTarget
     */
    public function setMaxScreen($maxScreen)
    {
        $this->maxScreen = $maxScreen;

        return $this;
    }

    /**
     * @return string
     */
    public function getMinDate()
    {
        return $this->minDate;
    }

    /**
     * @param string $minDate
     *
     * @return DrawingTarget
     */
    public function setMinDate($minDate)
    {
        $this->minDate = $minDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxDate()
    {
        return $this->maxDate;
    }

    /**
     * @param string $maxDate
     *
     * @return DrawingTarget
     */
    public function setMaxDate($maxDate)
    {
        $this->maxDate = $maxDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param string $groupId
     *
     * @return DrawingTarget
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogPath()
    {
        return $this->logPath;
    }

    /**
     * @param string $logPath
     *
     * @return DrawingTarget
     */
    public function setLogPath($logPath)
    {
        $this->logPath = $logPath;

        return $this;
    }

}