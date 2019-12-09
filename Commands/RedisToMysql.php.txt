<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\ClickHeat\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\ClickHeat\Config;
use Piwik\Plugins\ClickHeat\Importer\ImporterInterface;
use Piwik\Plugins\ClickHeat\Logger\LoggerImporterInterface;
use Piwik\Plugins\ClickHeat\Logger\RedisLogger;
use Piwik\Plugins\SitesManager\API;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedisToMysql extends ConsoleCommand
{

    /**
     * @var LoggerImporterInterface
     */
    protected $logger;

    /**
     * @var ImporterInterface
     */
    protected $importer;

    public function __construct($name = null)
    {
        parent::__construct($name);
        Config::init();
        $this->importer = StaticContainer::get('Piwik\Plugins\ClickHeat\Importer\MySqlImporter');
        $this->logger = new RedisLogger(Config::all());
    }

    protected function configure()
    {
        $this->setName('clickheat:redis_to_mysql');
        $this->setDescription('Process and clean logging data from Redis logger and put to MySQL adapter.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // loop all the groups (by site) and their data
        $sites = API::getInstance()->getAllSites();
        $importedCount = 0;
        foreach ($sites as $site) {
         // loop all groups in Redis
            $groups = $this->logger->getGroupsBySite($site['idsite']);
            foreach ($groups as $groupHash => $group) {
                $importedCount += $this->import($site['idsite'], $groupHash, $group);
            }
        }
        $output->writeln('Completed!');
        $output->writeln('Imported '. $importedCount .' records');
    }

    /**
     * @param $siteId
     * @param $groupHash
     * @param $group
     *
     * @return int
     */
    private function import($siteId, $groupHash, $group)
    {
        // check if group already existed
        $groupExist = $this->importer->getImportedGroup($siteId, $groupHash);
        if (!$groupExist) {
            $groupId = $this->importer->createImportedGroup($siteId, $groupHash, $group);
        } else {
            $groupId = $groupExist['id'];
        }
        // get logging data by group
        $data = $this->logger->getLoggingData($siteId, $group['name']);
        $imported = 0;
        foreach ($data as $attribute => $log) {
            $success = $this->importer->importLog($siteId, $groupId, $log);
            if ($success) {
                // imported, remove log
                $this->logger->removeLog($siteId, $group['name'], $attribute);
                $imported++;
            }
        }

        return $imported;
    }

}
