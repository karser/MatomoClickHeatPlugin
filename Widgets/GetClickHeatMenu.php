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
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\ClickHeat\Adapter\HeatmapAdapterInterface;
use Piwik\Plugins\ClickHeat\Config;
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
     * @var HeatmapAdapterInterface
     */
    protected $adapter;

    /**
     * GetClickHeatMenu constructor.
     */
    public function __construct()
    {
        $this->adapter = StaticContainer::get(Config::get('adapter'));
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
        Config::init();
        /** List of available groups */
        $conf = Config::all();
        /** Fix by Kowalikus: get the list of sites the current user has view access to */
        $idSite = (int) Common::getRequestVar('idSite');
        if (Piwik::isUserHasViewAccess($idSite) === false) {
            return false;
        }
        /** Screen sizes */
        $__selectScreens = [];
        for ($i = 0; $i < count($conf['__screenSizes']); $i++) {
            $__selectScreens[$conf['__screenSizes'][$i]] = ($conf['__screenSizes'][$i] === 0 ? Piwik::Translate('ClickHeat_LANG_ALL') : $conf['__screenSizes'][$i] . 'px');
        }

        /** Browsers */
	    $__selectBrowsers = [];
        foreach ($conf['__browsersList'] as $label => $name) {
            $__selectBrowsers[$label] = $label === 'unknown' ? Piwik::Translate('ClickHeat_LANG_UNKNOWN') : $name;
        }
	    $__selectBrowsers['all'] = Piwik::Translate('ClickHeat_LANG_ALL');
        
        $groups = $this->adapter->getGroups($idSite);

        $view = new View('@ClickHeat/view');
        $port = Helper::getServerPort();
        $data = [
            'clickheat_host'          => 'http://' . $_SERVER['SERVER_NAME'] . $port,
            'clickheat_path'          => CLICKHEAT_PATH,
            'clickheat_index'         => CLICKHEAT_INDEX_PATH,
            'clickheat_groups'        => $groups,
            'clickheat_browsers'      => $__selectBrowsers,
            'clickheat_screens'       => $__selectScreens,
            'clickheat_loading'       => str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_ERROR_LOADING')),
            'clickheat_cleaner'       => str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_CLEANER_RUNNING')),
            'clickheat_admincookie'   => str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_JAVASCRIPT_ADMIN_COOKIE')),
            'clickheat_alpha'         => $conf['alpha'],
            'clickheat_iframes'       => $conf['hideIframes'] === true ? 'true' : 'false',
            'clickheat_flashes'       => $conf['hideFlashes'] === true ? 'true' : 'false',
            'clickheat_force_heatmap' => $conf['heatmap'] === true ? ' checked="checked"' : '',
            'clickheat_jsokay'        => str_replace('\'', '\\\'', Piwik::Translate('ClickHeat_LANG_ERROR_JAVASCRIPT')),
            'clickheat_range'         => Common::getRequestVar('period'),
            'clickheat_date'          => Common::getRequestVar('date'),
        ];
        $view->assign($data);

        return $view->render();
    }

}