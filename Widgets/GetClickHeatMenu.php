<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\ClickHeat\Widgets;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\ClickHeat\Controller;
use Piwik\Plugins\ClickHeat\Model;
use Piwik\Plugins\ClickHeat\Utils\Helper;
use Piwik\Widget\Widget;
use Piwik\Widget\WidgetConfig;
use Piwik\View;

/**
 * This class allows you to add your own widget to the Piwik platform. In case you want to remove widgets from another
 * plugin please have a look at the "configureWidgetsList()" method.
 * To configure a widget simply call the corresponding methods as described in the API-Reference:
 * http://developer.piwik.org/api-reference/Piwik/Plugin\Widget
 */
class GetClickHeatMenu extends Widget
{
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * @var Model
     */
    protected $model;

    /**
     * GetClickHeatMenu constructor.
     *
     * @param Controller $controller
     * @param Model      $model
     */
    public function __construct(Controller $controller, Model $model)
    {
        $this->controller = $controller;
        $this->model = $model;
    }

    /**
     * @param WidgetConfig $config
     */
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('General_Visitors');
        $config->setSubcategoryId('Click Heat');
        $config->setName('ClickHeat_CLICK_HEAT_MENU');
        $config->setOrder(99);
        \Piwik\Piwik::checkUserIsNotAnonymous();
        $config->setIsEnabled(true);
        $config->setIsNotWidgetizable();
    }

    /**
     * This method renders the widget. It's on you how to generate the content of the widget.
     * As long as you return a string everything is fine. You can use for instance a "Piwik\View" to render a
     * twig template. In such a case don't forget to create a twig template (eg. myViewTemplate.twig) in the
     * "templates" directory of your plugin.
     *
     * @return string
     */
    public function render()
    {
        /** List of available groups */
        $conf = $this->controller->getConf();
        /** Fix by Kowalikus: get the list of sites the current user has view access to */
        $idSite = (int) Common::getRequestVar('idSite');
        if (Piwik::isUserHasViewAccess($idSite) === false) {
            return false;
        }
        $groups = $this->model->getGroupsBySite($idSite);
        /** Screen sizes */
        $__selectScreens = '';
        for ($i = 0; $i < count($conf['__screenSizes']); $i++) {
            $__selectScreens[$conf['__screenSizes'][$i]] = ($conf['__screenSizes'][$i] === 0 ? Piwik::Translate('ClickHeat_LANG_ALL') : $conf['__screenSizes'][$i] . 'px');
        }

        /** Browsers */
        $__selectBrowsers = ['all' => Piwik::Translate('ClickHeat_LANG_ALL')];
        foreach ($conf['__browsersList'] as $label => $name) {
            $__selectBrowsers[$label] = $label === 'unknown' ? Piwik::Translate('ClickHeat_LANG_UNKNOWN') : $name;
        }

        /** Date */
        $date = strtotime(Common::getRequestVar('date'));
        if ($date === false) {
            if ($conf['yesterday'] === true) {
                $date = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
            } else {
                $date = time();
            }
        }
        $__day = (int) date('d', $date);
        $__month = (int) date('m', $date);
        $__year = (int) date('Y', $date);

        $range = Common::getRequestVar('period');
        $range = $range[0];

        if (!in_array($range, ['d', 'm', 'w'])) {
            $range = 'd';
        }
        if ($range === 'w') {
            $startDay = $conf['start'] === 'm' ? 1 : 0;
            while (date('w', $date) != $startDay) {
                $date = mktime(0, 0, 0, date('m', $date), date('d', $date) - 1, date('Y', $date));
            }
            $__day = (int) date('d', $date);
            $__month = (int) date('m', $date);
            $__year = (int) date('Y', $date);
        } elseif ($range === 'm') {
            $__day = 1;
        }

        $view = new View('@ClickHeat/view');
        $port = Helper::getServerPort();
        $view->assign('clickheat_host', 'http://' . $_SERVER['SERVER_NAME'] . $port);
        $view->assign('clickheat_path', CLICKHEAT_PATH);
        $view->assign('clickheat_index', CLICKHEAT_INDEX_PATH);
        $view->assign('clickheat_groups', $groups);
        $view->assign('clickheat_browsers', $__selectBrowsers);
        $view->assign('clickheat_screens', $__selectScreens);
        $view->clickheat_loading = str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_ERROR_LOADING'));
        $view->clickheat_cleaner = str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_CLEANER_RUNNING'));
        $view->clickheat_admincookie = str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_JAVASCRIPT_ADMIN_COOKIE'));
        $view->clickheat_alpha = $conf['alpha'];
        $view->clickheat_iframes = $conf['hideIframes'] === true ? 'true' : 'false';
        $view->clickheat_flashes = $conf['hideFlashes'] === true ? 'true' : 'false';
        $view->clickheat_force_heatmap = $conf['heatmap'] === true ? ' checked="checked"' : '';
        $view->clickheat_jsokay = str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_ERROR_JAVASCRIPT'));
        $view->clickheat_day = $__day;
        $view->clickheat_month = $__month;
        $view->clickheat_year = $__year;
        $view->clickheat_range = $range;
        $view->clickheat_menu = '';

        return $view->render();
    }

}