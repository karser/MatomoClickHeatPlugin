<?php
namespace Piwik\Plugins\ClickHeat\Utils;


use Piwik\Common;

class CacheStorage
{
    /**
     * @param $cachePath
     *
     * @return bool|int
     */
    public function clean($cachePath)
    {
        if (is_dir($cachePath) === true) {
            return false;
        }
        $deletedFiles = 0;
        Common::printDebug("ClickHeat - clear cache directory:");
        $time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        $time -= 86400;
        $d = dir($cachePath . '/');
        while (($file = $d->read()) !== false) {
            if ($file[0] === '.') {
                continue;
            }
            $pos = strrpos($file, '.');
            if ($pos === false) {
                continue;
            }
            $ext = substr($file, $pos + 1);
            $filemtime = filemtime($d->path . $file);
            Common::printDebug(sprintf("&nbsp;&nbsp;File: %s %s seconds left", [$file, $filemtime - $time]));
            switch ($ext) {
                case 'html':
                case 'png':
                case 'png_temp':
                case 'png_log': {
                    if ($filemtime < $time) {
                        @unlink($d->path . $file);
                        $deletedFiles++;
                        continue;
                    }
                    break;
                }
            }
        }
        $d->close();

        return $deletedFiles;
    }
}