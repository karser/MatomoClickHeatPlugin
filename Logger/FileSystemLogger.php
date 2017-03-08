<?php
namespace Piwik\Plugins\ClickHeat\Logger;


use Piwik\Common;

class FileSystemLogger extends AbstractLogger
{
    const URL_LOGGING_FILE = 'url.txt';

    /**
     * @var array
     */
    protected $config = [
        'logPath'  => '',
        'fileSize' => 0,
        'flush'    => 0
    ];

    /**
     * {@inheritdoc}
     **/
    public function log($siteId, $groupName, $referrer, $browser, $screenSize, $posX, $posY)
    {
        $logPath = $this->getConfig('logPath');
        $fileSize = $this->getConfig('fileSize');
        $final = ltrim($siteId . ',' . $groupName, ',');
        /* Limit file size */
        $processingLog = $this->getLogFile($logPath . $final);
        if ($fileSize !== 0) {
            if (file_exists($processingLog) && filesize($processingLog) > $fileSize) {
                Common::printDebug('ClickHeat: Filesize reached limit');
            }
        }
        $f = fopen($processingLog, 'a');
        if (!is_resource($f)) {
            /* Can't open the log, let's try to create the directory */
            if (!is_dir(dirname($logPath))) {
                if (!mkdir(dirname($logPath))) {
                    Common::printDebug('ClickHeat: Cannot create log directory: ' . $logPath);
                }
            }
            if (!is_dir($logPath . $final)) {
                if (!mkdir($logPath . $final)) {
                    Common::printDebug('ClickHeat: Cannot create log directory: ' . $logPath . $final);
                }
                if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '') {
                    $f = fopen($logPath . $final . '/' . self::URL_LOGGING_FILE, 'w');
                    fputs($f, str_replace('debugclickheat', '', $_SERVER['HTTP_REFERER']) . '>0>0>0');
                    fclose($f);
                }
            }
            $f = fopen($processingLog, 'a');
        }
        if (!is_resource($f)) {
            Common::printDebug('ClickHeat: Error, file not writable');

            return false;
        }
        fputs($f, ((int) $posX) . '|' . ((int) $posY) . '|' . ((int) $screenSize) . '|' . $browser . '|' . ((int) $siteId) . "\n");

        return true;
    }

    public function clean()
    {
        $logDir = dir($this->getConfig('logPath') . '/');
        $deletedFiles = 0;
        $deletedDirs = 0;
        while (($dir = $logDir->read()) !== false) {
            if ($dir[0] === '.' || !is_dir($logDir->path . $dir)) {
                continue;
            }
            $d = dir($logDir->path . $dir . '/');
            $deletedAll = true;
            $oldestDate = mktime(0, 0, 0, date('m'), date('d') - $this->getConfig('flush'), date('Y'));
            while (($file = $d->read()) !== false) {
                if ($file[0] === '.' || $file === self::URL_LOGGING_FILE) {
                    continue;
                }
                $ext = explode('.', $file);
                if (count($ext) !== 2) {
                    $deletedAll = false;
                    continue;
                }
                $filemtime = filemtime($d->path . $file);
                if ($ext[1] === 'log' && $filemtime <= $oldestDate) {
                    @unlink($d->path . $file);
                    $deletedFiles++;
                    continue;
                }
                $deletedAll = false;
            }
            /** If every log file (but the url.txt) has been deleted, then we should delete the directory too */
            if ($deletedAll === true) {
                @unlink($d->path . '/' . self::URL_LOGGING_FILE);
                $deletedFiles++;
                @rmdir($d->path);
                $deletedDirs++;
            }
            $d->close();
        }
        $logDir->close();

        return [
            'deletedFiles' => $deletedFiles,
            'deletedDirs'  => $deletedDirs
        ];
    }

    /**
     * @param $folder
     *
     * @return string
     */
    private function getLogFile($folder)
    {
        return $folder . '/' . date('Y-m-d') . '.log';
    }

}