# Piwik ClickHeat Plugin

## Description
ClickHeat 3 is a visual heatmap of clicks on a HTML page for Matomo/Piwik 3.x.
This plugin based on [Dugwood's ClickHeat version 1.14](https://github.com/dugwood/clickheat)
and forked from [Trung Tran's fork](https://github.com/trungtnm/plugin-clickheat)
of [PiwikJapan ClickHeat plugin](https://github.com/piwikjapan/plugin-clickheat).
It is an OpenSource software, released under GPL licence, and free of charge. 

![image](https://user-images.githubusercontent.com/1675033/70543449-15412000-1b73-11ea-9f1a-3b6a4b9399fd.png)

## Installation

### Method 1: upload zip archive
Run `make zip` and build the plugin file `MatomoClickHeatPlugin.zip`.
Then open Matomo Settings / System / Plugins and upload the plugin file.

### Method 2: copy the plugin folder
You have to copy the source code of this plugin to your `piwik/plugins` folder.
Since a weird issue, Piwik does not recognize this plugin, you have to manually add it to your Piwik `config.ini.php` as below:
```
...
Plugins[] = ClickHeat
```
Then browse your Piwik `System > Plugins`, this plugin will be automatically installed.

This plugin installer will make directories:
* piwik/tmp/cache/clickheat/cache
* piwik/tmp/cache/clickheat/logs.

And these MySQL tables : `piwik_click_heat`, `piwik_click_heat_group`

The default logger is MysqlLogger. In case you want to use `Redis` as `Logger`,
you need to install `predis/predis` via Composer to make this plugin works properly.
```
composer require predis/predis
```

This plugin uses a different tracker. Please click on the link "JavaScript" in `Visitors > Click Heat` menu and put the special Javascript codes into your website. It is recommended to name your `Group` for easier management.
## Logger and Adapter
- Logger used to store logging data of users's clicks on webpages.
- Adapter used to fetch logging data and showing in heat map report.

    Each logger and adapter is associated with a different data storage system (see in following sections). You can combine logger and adapter together for matching your needs and better performance, such as: use `RedisLogger` for fast serving and use `MySqlAdapter` for better data storage.
    
    Available loggers: `Redis`, `MySQL`, `File system`
    Available adapters: `MySQL`, `File system`
    
### Logger
You can use different loggers to track clicks on webpage, currently supported:
- `Piwik\Plugins\ClickHeat\Logger\FileSystemLogger` - store logging data in text files.
- `Piwik\Plugins\ClickHeat\Logger\MysqlLogger` - store logging data in MySQL databases, tables needed for this logger will be created when you installed the plugin.
- `Piwik\Plugins\ClickHeat\Logger\RedisLogger` - store logging data in Redis server.

Currently `Redis` only used as a `Logger` and can not create heat map via it. So you need to run a command to import data to another `Adapter` like MySQL. 
```
./console clickheat:redis_to_msql
```
We recommend to put this command to a cron job so you don't need to run it manually.
```
# cron job that run every 5minutes
*/5 * * * * /var/www/piwik/console clickheat:redis_to_msql
```
### Adapter
After logging clicks of webpages, adapters  used to retrieve data from associated storing system and generate heat map report, currently supported `Adapter`:
- `Piwik\Plugins\ClickHeat\Adapter\FileSystemAdapter`
- `Piwik\Plugins\ClickHeat\Adapter\MySqlAdapter`

## FAQs
__And what functions are not included in this feature ?__

* remove special addresses defined on the control panel.
* remove special browsers defined on the control panel.
* filters based on added segmentation

__Where is the coordinate information from the browser ?__

ClickHeat plugin uses text files to record the coordinate data of each browser in directory: yourpiwik/tmp/cache/clickheat/logs.

__Does it withstands high traffics ?__

This plugin uses minimal text to record data and file based logging. And when click.php is called from a special Javascript for cgi, just append text on end of the each file. And when you analyze the click data and make a heatmap, plugin will create cached heatmap as png image file. 

Therefore, we expect the plugin light works, but we don't know what load it has under Piwik 2.x. So we are very glad, when you inform us about your situation. 

Please see the link [Performance and optimization](http://www.labsmedia.com/clickheat/156894.html) about system resources. If you want performance, you need to avoid to use a cgi, that is possible. It method is explained on the link. 

__New click data were added, but not updated heatmap. Why ?__

Plugin places heatmap images in the cache directory: yourpiwik/tmp/cache/clickheat/cache. Therefore when you suddenly met with such probrem, you can delete cache files, but __do not delete cache directory__.

__Showed a heatmap, but not overlay a heatmap to the target web page. Why ?__

Check that your website does not set the HTTP header __X-FRAME-OPTIONS__ to __SAMEORIGIN__ as this will prevent this plugin from iframing your website for the heatmap report. Please see [Page Overlay Troubleshooting](http://piwik.org/docs/page-overlay/#page-overlay-troubleshooting), that is same problem.

## TODO

 - Refactor code.
 - Correct File System logger and adapter.
 - Implement Piwik 3 System settings.
 - Submit to Marketplace.

## License
GPL v3 or later