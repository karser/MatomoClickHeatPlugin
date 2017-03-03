<?php

/**
 * ClickHeat - Clicks' heatmap
 *
 * @link http://www.dugwood.com/clickheat/index.html
 * @license http://www.gnu.org/licenses/gpl-3.0.html Gpl v3 or later
 * @version $Id$
 *
 * @package Piwik\Plugins\ClickHeat
 */

namespace Piwik\Plugins\ClickHeat;

use Piwik\Container\StaticContainer;
use Piwik\Cookie;
use Piwik\Date;
use Piwik\IP;
use Piwik\Network\IPUtils;
use Piwik\Plugins\ClickHeat\Adapter\HeatMapAdapterFactory;
use Piwik\Plugins\ClickHeat\Logger\AbstractLogger;
use Piwik\Plugins\ClickHeat\Utils\CacheStorage;
use Piwik\Plugins\ClickHeat\Utils\DrawingTarget;
use Piwik\Plugins\ClickHeat\Utils\Helper;
use Piwik\Plugins\ClickHeat\Utils\ImprovedHeatmap;
use Piwik\Site;
use Piwik\Translate;
use Piwik\Piwik;
use Piwik\Common;

class Controller extends \Piwik\Plugin\Controller
{
    static $conf = [];

    /**
     * @var AbstractLogger
     */
    static $logger;

    public function init()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();
        $__languages = ['bg', 'cz', 'de', 'en', 'es', 'fr', 'hu', 'id', 'it', 'ja', 'nl', 'pl', 'pt', 'ro', 'ru', 'sr', 'tr', 'uk', 'zh'];

        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '') {
            $realPath = &$_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] !== '') {
            $realPath = &$_SERVER['SCRIPT_NAME'];
        } else {
            exit(LANG_UNKNOWN_DIR);
        }

        /** First of all, check if we are inside Piwik */
        $dirName = dirname($realPath);
        if ($dirName === '/') {
            $dirName = '';
        }
        if (!defined('CLICKHEAT_PATH')) {
            define('CLICKHEAT_PATH', $dirName . '/plugins/ClickHeat/libs/');
        }
        if (!defined('CLICKHEAT_INDEX_PATH')) {
            define('CLICKHEAT_INDEX_PATH', 'index.php?module=ClickHeat&');
        }
        if (!defined('CLICKHEAT_ROOT')) {
            define('CLICKHEAT_ROOT', PIWIK_INCLUDE_PATH . '/plugins/ClickHeat/libs/');
        }
        if (!defined('CLICKHEAT_CONFIG')) {
            define('CLICKHEAT_CONFIG', PIWIK_INCLUDE_PATH . '/plugins/ClickHeat/clickheat_config.php');
        }
        if (!defined('IS_PIWIK_MODULE')) {
            define('IS_PIWIK_MODULE', true);
        }
        if (!defined('CLICKHEAT_LANGUAGE')) {
            define('CLICKHEAT_LANGUAGE', Translate::getLanguageToLoad());
        }


        if (!defined('CLICKHEAT_ADMIN')) {
            if (Piwik::hasUserSuperUserAccess()) {
                define('CLICKHEAT_ADMIN', true);
            } else {
                define('CLICKHEAT_ADMIN', false);
            }
        }

        require(CLICKHEAT_CONFIG);
        // Manually load the libs since Click Heat is not available in Composer
        require_once(CLICKHEAT_ROOT . "classes/Heatmap.class.php");
        require_once(CLICKHEAT_ROOT . "classes/HeatmapFromClicks.class.php");
        /** Specific definitions */
        $clickheatConf['__screenSizes'] = [0/** Must start with 0 */, 640, 800, 1024, 1280, 1440, 1600, 1800];
        $clickheatConf['__browsersList'] = ['all' => '', 'firefox' => 'Firefox', 'chrome' => 'Google Chrome', 'msie' => 'Internet Explorer', 'safari' => 'Safari', 'opera' => 'Opera', 'kmeleon' => 'K-meleon', 'unknown' => ''];

        $this->setConf($clickheatConf);
        if (!self::$logger) {
            $logger = self::$conf['logger'];
            self::$logger = new $logger($clickheatConf);
        }
    }

    /** It's a static class, but PHP 4 doesn't know about «static»
     *
     * @param array $conf
     *
     * @return bool
     */
    private function setConf($conf = [])
    {
        if (is_array($conf) && count($conf)) {
            self::$conf = $conf;

            return true;
        }

        return false;
    }

    public static function getConf()
    {
        return self::$conf;
    }

    public function getGroupUrl()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();
        $group = str_replace('/', '', Common::getRequestVar('group'));
        return self::$logger->getGroupUrl($group);
    }

    public function javascript()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();
        foreach (['', '_GROUP', '_GROUP0', '_GROUP1', '_GROUP2', '_GROUP3', '_DEBUG', '_QUOTA', '_IMAGE', '_SHORT', '_PASTE'] as $value) {
            define("LANG_JAVASCRIPT$value", Piwik::Translate("ClickHeat_LANG_JAVASCRIPT$value"));
        }
        require_once(CLICKHEAT_ROOT . 'javascript.php');
    }

    public function layout()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();
        include(CLICKHEAT_ROOT . 'layout.php');
    }

    public function generate()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();

        /* Time and memory limits */
        @set_time_limit(120);
        @ini_set('memory_limit', self::$conf['memory'] . 'M');
        /* Browser */
        $browser = $this->getBrowser();
        $screenInfo = $this->getScreenSize();
        if (is_null($screenInfo)) {
            return $this->error(LANG_ERROR_SCREEN);
        }
        list($screen, $width, $minScreen, $maxScreen) = $screenInfo;
        /* Selected Group */
        $group = str_replace('/', '', Common::getRequestVar('group'));
        $isRequestHeatMap = Common::getRequestVar('heatmap') === '1';
        /* Date and days */
        $now = isset($_SERVER['REQUEST_TIME']) ? date('Y-m-d', $_SERVER['REQUEST_TIME']) : date('Y-m-d');
        $requestDate = Common::getRequestVar('date') ? Common::getRequestVar('date') : $now;
        $range = Common::getRequestVar('range');
        list($minDate, $maxDate, $cacheTime) = $this->getDate($requestDate, $range);
        $imagePath = $group . '-' . $requestDate . '-' . $range . '-' . $screen . '-' . $browser . '-' . ($isRequestHeatMap === true ? 'heat' : 'click');
        $htmlPath = self::$conf['cachePath'] . $imagePath . '.html';
        /* If images are already created, just stop script here if these have less than 120 seconds (today's log) or 86400 seconds (old logs) */
        if (file_exists($htmlPath) && filemtime($htmlPath) > strtotime($now) - $cacheTime) {
            return readfile($htmlPath);
        }
        $target = new DrawingTarget([
            'groupId'   => $group,
            'browser'   => $browser,
            'minScreen' => $minScreen,
            'maxScreen' => $maxScreen,
            'minDate'   => $minDate,
            'maxDate'   => $maxDate,
        ]);
        $heatmapObject = $this->createHeatMap($target, $isRequestHeatMap, $browser, $minScreen, $maxScreen, $imagePath);
        /* Add files */
//        if (method_exists($heatmapObject, 'addFile')) {
//            // TODO : move this to adapter
//            for ($day = 0; $day < $days; $day++) {
//                $currentDate = date('Y-m-d', mktime(0, 0, 0, date('m', $dateStamp), date('d', $dateStamp) + $day, date('Y', $dateStamp)));
//                $this->addFile(self::$conf['logPath'] . $group . '/' . $currentDate . '.log');
//            }
//        }
        $result = $heatmapObject->generate($width);
        if ($result === false) {
            return $this->error($heatmapObject->error);
        }
        $html = '';
        for ($i = 0; $i < $result['count']; $i++) {
            $html .= '<img src="' . CLICKHEAT_INDEX_PATH . 'action=png&amp;file=' . $result['filenames'][$i] . '&amp;rand=' . strtotime($now) . '" width="' . $result['width'] . '" height="' . $result['height'] . '" alt="" id="heatmap-' . $i . '" /><br />';
        }
//        /* Save the HTML code to speed up following queries (only over two minutes) */
//        $f = fopen($htmlPath, 'w');
//        fputs($f, $html);
//        fclose($f);

        return $html;
    }

    public function png()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();
        $conf = $this->getConf();
        $imagePath = $conf['cachePath'] . (isset($_GET['file']) ? str_replace('/', '', $_GET['file']) : '**unknown**');

        header('Content-Type: image/png');
        if (file_exists($imagePath)) {
            readfile($imagePath);
        } else {
            readfile(CLICKHEAT_ROOT . 'images/warning.png');
        }
    }

    public function cleaner()
    {
        // if you are not valid user, force login.
        Piwik::checkUserIsNotAnonymous();
        $config = self::$conf;
        if ($config['flush']) {
            self::$logger->clean();
        }
        $cacheCleaner = StaticContainer::get(CacheStorage::class);
        $cacheCleaner->clean($config['cachePath']);
    }

    public function click()
    {
        $validateRequest = $this->isValidRequest();
        if ($validateRequest !== true) {
            return $validateRequest;
        }
        @ignore_user_abort(true);
        self::$logger->log(
            Common::getRequestVar('s'),
            Common::getRequestVar('g'),
            $_SERVER['HTTP_REFERER'],
            Helper::getBrowser(Common::getRequestVar('b')),
            Common::getRequestVar('w'),
            Common::getRequestVar('x'),
            Common::getRequestVar('y')
        );

        return 'ClickHeat: OK';

    }

    /**
     * @return bool
     */
    protected function isValidRequest()
    {
        $config = self::$conf;
        $group = Common::getRequestVar('g');
        if (
            !isset($_GET['x'])
            || !isset($_GET['y'])
            || !isset($_GET['w'])
            || empty($group)
            || !isset($_GET['s'])
            || !isset($_GET['b'])
            || !isset($_GET['c'])
        ) {
            return "\"ClickHeat: Parameters or config error\"";
        }
        // check referer
        if (is_array($config['referers'])) {
            if (!isset($_SERVER['HTTP_REFERER'])) {
                return 'ClickHeat: No domain in referer';
            }
            $referer = parse_url($_SERVER['HTTP_REFERER']);
            if (!in_array($referer['host'], $config['referers'])) {
                return 'ClickHeat: Forbidden domain (' . $referer['host'] . '), change or remove security settings in the /config panel to allow this one';
            }
        }
        // check valid group
        if (is_array($config['groups'])) {
            if (!in_array($group, $config['groups'])) {
                return 'ClickHeat: Forbidden group (' . $group . '), change or remove security settings in the config panel to allow this one';
            }

            return false;
        }
        // check browser
        $browser = Helper::getBrowser(Common::getRequestVar('b'));
        if ($browser === '') {
            return 'ClickHeat: Browser empty';
        }
        if ($isSkipped = $this->isSkippable()) {
            return $isSkipped;
        }

        return true;
    }

    /**
     * @return bool|string
     */
    private function isSkippable()
    {
        $adminCookie = new Cookie('clickheat-admin');
        if ($adminCookie->isCookieFound()) {
            return "ClickHeat: OK, but click not logged as you selected it in the admin panel";
        } else {
            $site = new Site(Common::getRequestVar('s'));
            if ($excludedIps = $site->getExcludedIps()) {
                $ip = IPUtils::stringToBinaryIP(\Piwik\Network\IP::fromStringIP(IP::getIpFromHeader()));
                if (Helper::isIpInRange($ip, $excludedIps)) {
                    return 'OK, but click not logged as you prevent this IP to be tracked in Piwik\'s configuration';
                }
            }
        }

        return false;
    }

    /**
     * @param $error
     *
     * @return string
     */
    function error($error)
    {
        return '&nbsp;<div style="line-height:20px;"><span class="error">' . $error . '</span></div>';
    }

    /**
     * @param DrawingTarget $target
     * @param               $isRequestHeatMap
     * @param               $browser
     * @param               $minScreen
     * @param               $maxScreen
     * @param               $imagePath
     *
     * @return ImprovedHeatmap
     */
    private function createHeatMap(DrawingTarget $target, $isRequestHeatMap, $browser, $minScreen, $maxScreen, $imagePath)
    {
        $obj = HeatMapAdapterFactory::create(self::$logger);
        $obj->setTarget($target);
        $obj->heatmap = $isRequestHeatMap;
        $obj->browser = $browser;
        $obj->minScreen = $minScreen;
        $obj->maxScreen = $maxScreen;
        $obj->layout = ['', 0, 0, 0]; // not support change layout at this time
        $obj->memory = self::$conf['memory'] * 1048576;
        $obj->step = self::$conf['step'];
        $obj->dot = self::$conf['dot'];
        $obj->palette = self::$conf['palette'];
        $obj->path = self::$conf['cachePath'];
        $obj->cache = self::$conf['cachePath'];
        $obj->file = $imagePath . '-%d.png';

        return $obj;
    }

    /**
     * @return string
     */
    private function getBrowser()
    {
        $browser = Common::getRequestVar('browser');
        if (!isset(self::$conf['__browsersList'][$browser])) {
            $browser = 'all';
        }

        return $browser;
    }

    /**
     * @return array|bool
     */
    private function getScreenSize()
    {
        $screen = Common::getRequestVar('screen', 0);
        $minScreen = 0;
        if ($screen < 0) {
            $width = abs($screen);
            $maxScreen = 3000;
        } else {
            $maxScreen = $screen;
            if (!in_array($screen, self::$conf['__screenSizes']) || $screen === 0) {
                return false;
            }
            for ($i = 1; $i < count(self::$conf['__screenSizes']); $i++) {
                if (self::$conf['__screenSizes'][$i] === $screen) {
                    $minScreen = self::$conf['__screenSizes'][$i - 1];
                    break;
                }
            }
            $width = $screen - 25;
        }

        return [
            $screen,
            $width,
            $minScreen,
            $maxScreen
        ];
    }

    /**
     * @param $requestDate
     * @param $requestRange
     *
     * @return array
     */
    private function getDate($requestDate, $requestRange)
    {
        $range = in_array($requestRange, ['d', 'w', 'm']) ? $requestRange : 'd';
        $date = explode(',', $requestDate);
        if (count($date) == 2) {
            $minDate = $date[0];
            $maxDate = $date[1];
        } else {
            $minDate = $date[0];
        }
        switch ($range) {
            case 'd': {
                $maxDate = $minDate;
                $cacheTime = date('dmy', $requestDate) !== date('dmy') ? 86400 : 120;
                break;
            }
            case 'w': {
                $maxDate = date('Y-m-d', strtotime($minDate . " +7 days"));
                $cacheTime = date('Wy', $requestDate) !== date('Wy') ? 86400 : 120;
                break;
            }
            case 'm': {
                $days = date('t', $requestDate);
                $maxDate = date('Y-m-d', strtotime($minDate . " +{$days} days"));
                $cacheTime = date('my', $requestDate) !== date('my') ? 86400 : 120;
                break;
            }
        }

        return [
            $minDate,
            $maxDate,
            $cacheTime
        ];
    }

}