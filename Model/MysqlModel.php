<?php
/**
 * Created by PhpStorm.
 * User: Trung Tran
 * Date: 2/28/17
 * Time: 14:00
 */

namespace Piwik\Plugins\ClickHeat\Model;

use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Plugins\ClickHeat\Utils\DrawingTarget;

class MysqlModel implements LoggerModelInterface
{
    private static $rawPrefix = 'click_heat';

    private static $rawGroupPrefix = 'click_heat_group';

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $groupTable;

    public function __construct()
    {
        $this->table = Common::prefixTable(self::$rawPrefix);
        $this->groupTable = Common::prefixTable(self::$rawGroupPrefix);
    }

    /**
     * {@inheritdoc}
     */
    public function addLog($siteId, $groupId, $browser, $screenSize, $posX, $posY, $date = '')
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        $data = [
            'group_id'    => $groupId,
            'browser'     => $browser,
            'screen_size' => $screenSize,
            'pos_x'       => $posX,
            'pos_y'       => $posY,
            'date'        => $date
        ];
        $this->getDb()->insert($this->table, $data);

        return $this->getDb()->lastInsertId();
    }

    /**
     * @return Db|Db\AdapterInterface|\Piwik\Tracker\Db
     */
    private function getDb()
    {
        return Db::get();
    }

    public static function install()
    {
        // group table
        $groupTable = "
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `url` text NOT NULL,
            `idsite` int(11) unsigned NOT NULL,
            PRIMARY KEY (`id`)
        )";
        DbHelper::createTable(self::$rawPrefix, $groupTable);

        // tracking table
        $table = "
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `screen_size` int(11) unsigned NOT NULL,
            `pos_x` int(10) unsigned NOT NULL,
            `pos_y` int(10) unsigned NOT NULL,
            `date` date NOT NULL,
            `browser` varchar(20) DEFAULT NULL,
            `group_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `click_heat_group` (`group_id`),
            CONSTRAINT `click_heat_group` FOREIGN KEY (`group_id`) REFERENCES `" . Common::prefixTable(self::$rawGroupPrefix) . "` (`id`)
        )";
        DbHelper::createTable(self::$rawPrefix, $table);
    }

    public static function uninstall()
    {
        Db::dropTables(Common::prefixTable(self::$rawPrefix));
    }

    /**
     * {@inheritdoc}
     */
    public function cleanLogging($days)
    {
        $daysToRemove = date('Y-m-d', strtotime("-{$days} days"));
        $sql = "DELETE FROM `{$this->table}` WHERE `date` < ?";

        return $this->getDb()->query($sql, [$daysToRemove]);
    }

    /**
     * @param               $minY
     * @param               $maxY
     * @param DrawingTarget $drawingTarget
     * @param int           $limit
     *
     * @return array
     */
    public function fetchData($minY, $maxY, DrawingTarget $drawingTarget, $limit = 1000)
    {
        $query = "SELECT `pos_x`, `pos_y` FROM `{$this->table}` WHERE `pos_y` BETWEEN ? AND ?";
        $params = [$minY, $maxY];
        list($query, $params) = $this->addDrawingConditionQuery($query, $params, $drawingTarget);
        if ($limit > 0) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }
        $results = $this->getDb()->fetchAll($query, $params);

        return $results;
    }

    /**
     * @param DrawingTarget $drawingTarget
     *
     * @return mixed
     */
    public function getDrawingMaxY(DrawingTarget $drawingTarget)
    {
        $query = "SELECT MAX(`pos_y`) as `maxY` FROM `{$this->table}`";
        $params = [];
        list($query, $params) = $this->addDrawingConditionQuery($query, $params, $drawingTarget);
        $result = $this->getDb()->fetchRow($query, $params);

        return isset($result['maxY']) ? $result['maxY'] : null;
    }

    /**
     * @param               $query
     * @param array         $params
     * @param DrawingTarget $drawingTarget
     *
     * @return array
     */
    private function addDrawingConditionQuery($query, array $params, DrawingTarget $drawingTarget)
    {
        if (!strpos(strtolower($query), 'where')) {
            $query .= " WHERE `group_id` = ?";
        } else {
            $query .= " AND `group_id` = ?";
        }
        $params[] = $drawingTarget->getGroupId();

        if ($drawingTarget->getBrowser() && $drawingTarget->getBrowser() != 'all') {
            $query .= " AND `browser` = ?";
            $params[] = $drawingTarget->getBrowser();
        }
        if ($drawingTarget->getMinScreen()) {
            $query .= " AND `screen_size` >= ?";
            $params[] = $drawingTarget->getMinScreen();
        }
        if ($drawingTarget->getMaxScreen()) {
            $query .= " AND `screen_size` <= ?";
            $params[] = $drawingTarget->getMaxScreen();
        }
        if ($drawingTarget->getMinDate()) {
            $query .= " AND `date` >= ?";
            $params[] = $drawingTarget->getMinDate();
        }
        if ($drawingTarget->getMaxDate()) {
            $query .= " AND `date` <= ?";
            $params[] = $drawingTarget->getMaxDate();
        }

        return [
            $query,
            $params
        ];
    }

    /**
     * @param $groupName
     *
     * @param $siteId
     *
     * @return mixed
     */
    public function getGroupByName($groupName, $siteId)
    {
        $query = "SELECT * FROM `{$this->groupTable}` WHERE `name` = ? AND `idsite` = ?";

        return $this->getDb()->fetchRow($query, [$groupName, $siteId]);
    }

    /**
     * @param        $groupName
     * @param        $siteId
     * @param string $url
     *
     * @return int
     */
    public function createGroup($groupName, $siteId, $url = '')
    {
        $data = [
            'name'   => $groupName,
            'idsite' => $siteId,
            'url'    => $url
        ];
        $this->getDb()->insert($this->groupTable, $data);

        return $this->getDb()->lastInsertId();
    }

    /**
     * Get group by its ID
     *
     * @param $groupId
     *
     * @return mixed
     */
    public function getGroup($groupId)
    {
        $query = "SELECT * FROM `{$this->groupTable}` WHERE `id` = ?";

        return $this->getDb()->fetchRow($query, [$groupId]);
    }

    /**
     * Get groups by site ID
     *
     * @param $siteId
     *
     * @return array
     */
    public function getGroupsBySite($siteId)
    {
        $query = "SELECT * FROM `{$this->groupTable}` WHERE `idsite` = ? ORDER BY `name` ASC";
        $groups = $this->getDb()->fetchAll($query, [$siteId]);
        if ($groups) {
            return array_column($groups, 'name', 'id');
        }

        return [];
    }
}
