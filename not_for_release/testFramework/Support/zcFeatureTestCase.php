<?php
/**
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Support;

use GuzzleHttp\Client;
use notifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;

/**
 *
 */
abstract class zcFeatureTestCase extends TestCase
{
    public $testContextsAvailable = ['admin', 'catalog', 'install'];
    protected  $configPath = '/not_for_release/testFramework/Support/ZencartConfigure';

    /**
     * @param TestResult|null $result
     * @return TestResult
     *
     * This allows us to run in full isolation mode including
     * classes, functions, and defined statements
     */
    public function run(TestResult $result = null): TestResult
    {
        $this->setPreserveGlobalState(false);
        return parent::run($result);
    }

    public function setup(): void
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 'true');
        define('DIR_FS_TESTROOT', getcwd());
        echo "\n" . "Test Root  = " . getcwd() . "\n";

        $this->user = $this->detectUser();
        echo "\n" . "Found User = " . $this->user . "\n";

        $this->context = $this->setContext($this->testContext);
        echo "\n" . "Context = " . $this->context . "\n";

        $this->loadZencartConfigures($this->detectUser(), $this->context);

        $p = DIR_FS_TESTROOT . '/zc_install/includes/classes/ParseSqlFile.php';
        require_once $p;

        //START LOAD DB
        $parser = new \ParseSqlFile();

        $options = [
            'db_host' => DB_SERVER,
            'db_user' => DB_SERVER_USERNAME,
            'db_password' => DB_SERVER_PASSWORD,
            'db_name' => DB_DATABASE,
            'db_prefix' => DB_PREFIX,
            'db_charset' => DB_CHARSET,
            'db_type' => DB_TYPE
        ];

        $parser->setOptions($options);
        $parser->getConnection(DIR_FS_TESTROOT);

        $installSQL = DIR_FS_TESTROOT . '/zc_install/sql/install/mysql_zencart.sql';
        $parser->parseSqlFile($installSQL);

        $demoSQL = DIR_FS_TESTROOT . '/zc_install/sql/demo/mysql_demo.sql';
        $parser->parseSqlFile($demoSQL);

        $this->client = new Client();

        // END LOAD DB


    }

    protected function setContext(string $context) : string
    {
        if (in_array($context, $this->testContextsAvailable)) {
            return $context;
        }
        return 'admin';
    }

    protected function loadZencartConfigures($user, $context)
    {
        if ($context === 'install') {
            return;
        }
        $f = DIR_FS_TESTROOT . $this->configPath . '/' . 'default.' . $context . '.configure.php';

        $filepath = $user . '.' . $context . '.configure.php';
        $f = DIR_FS_TESTROOT . $this->configPath . '/' . $filepath;
        echo "\n"  . "looking for config = " . $f . "\n";
        if (!file_exists($f)) {
            $f = DIR_FS_TESTROOT . $this->configPath . '/' . $filepath;
        }
        echo "\n"  . "using configure " . $f . "\n";
        require($f);
    }


    public function detectUser()
    {
        $user = $_SERVER['USER'] ?? $_SERVER['MY_USER'];
        if (defined('GITLAB_CI')) {
            $user = 'runner';
        }
        return $user;
    }


}
