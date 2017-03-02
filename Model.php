<?php
/**
 * Created by PhpStorm.
 * User: Trung Tran
 * Date: 2/28/17
 * Time: 14:00
 */

namespace Piwik\Plugins\ClickHeat;

use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;

class Model
{
    private static $rawPrefix = 'click_heat';

    /**
     * @var string
     */
    private $table;

    public function __construct()
    {
        $this->table = Common::prefixTable(self::$rawPrefix);
    }

    /**
     * Add click heat log
     *
     * @param $siteID
     * @param $group
     * @param $browser
     * @param $screenSize
     * @param $posX
     * @param $posY
     *
     * @return int
     * @internal param $topPos
     * @internal param $leftPos
     */
    public function addLog($siteID, $group, $browser, $screenSize, $posX, $posY)
    {
        $data = [
            'idsite'      => $siteID,
            'group'       => $group,
            'browser'     => $browser,
            'screen_size' => $screenSize,
            'pos_x'       => $posX,
            'pos_y'       => $posY,
            'date'        => date('Y-m-d')
        ];
        $this->getDb()->insert($this->table, $data);

        return $this->getDb()->lastInsertId();
    }

    private function getNextReportId()
    {
        $db = $this->getDb();
        $idReport = $db->fetchOne("SELECT max(idreport) + 1 FROM " . $this->table);

        if ($idReport == false) {
            $idReport = 1;
        }

        return $idReport;
    }

    private function getDb()
    {
        return Db::get();
    }

    public static function install()
    {
        $table = "
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `group` VARCHAR(255) NOT NULL,
            `screen_size` INT(11) unsigned NOT NULL,
            `pos_x` INT(10) unsigned NOT NULL,
            `pos_y` INT(10) unsigned NOT NULL,
            `idsite` INT(11) unsigned NOT NULL,
            `browser` VARCHAR(20) DEFAULT NULL,
            `date` DATE NOT NULL,
            PRIMARY KEY (`id`)
        )";
        DbHelper::createTable(self::$rawPrefix, $table);
    }

    public static function uninstall()
    {
        Db::dropTables(Common::prefixTable(self::$rawPrefix));
    }

    /**
     * @param $days
     *
     * @return int|\Zend_Db_Statement
     */
    public function cleanLogging($days)
    {
        $daysToRemove = date('Y-m-d', strtotime("-{$days} days"));
        $sql = "DELETE FROM " . $this->table . " WHERE `date` < ?";

        return $this->getDb()->query($sql, [$daysToRemove]);
    }
}
