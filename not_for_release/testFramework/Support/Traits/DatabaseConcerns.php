<?php

namespace Tests\Support\Traits;

use App\Services\MigrationsRunner;
use App\Services\SeederRunner;
use Illuminate\Database\Capsule\Manager as Capsule;
use InitialSeeders\DatabaseSeeder;

trait DatabaseConcerns
{
    public static function databaseSetup(): void
    {
        global $db;
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => DB_TYPE,
            'host'      => DB_SERVER,
            'database'  => DB_DATABASE,
            'username'  => DB_SERVER_USERNAME,
            'password'  => DB_SERVER_PASSWORD,
            'charset'   => DB_CHARSET,
            // do not pass prefix; this is included in the table definition
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        if (!defined('DIR_FS_ROOT')) {
            define('DIR_FS_ROOT', ROOTCWD);
        }
        if (!defined('DIR_FS_LOGS')) {
            define('DIR_FS_LOGS', ROOTCWD);
        }
        if (!defined('DEBUG_LOG_FOLDER')) define('DEBUG_LOG_FOLDER', DIR_FS_LOGS);
        if (!defined('IS_ADMIN_FLAG')) {
            define('IS_ADMIN_FLAG', false);
        }
        //require(ROOTCWD . 'includes/classes/db/' .DB_TYPE . '/query_factory.php');
        $db = new \queryFactory();
        $db->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    }

    public static function runMigrations()
    {
        echo 'Running Migrations' . PHP_EOL;
        $options = [
            'db_host' => DB_SERVER,
            'db_user' => DB_SERVER_USERNAME,
            'db_password' => DB_SERVER_PASSWORD,
            'db_name' => DB_DATABASE,
            'db_charset' => DB_CHARSET,
            'db_prefix' => '',
            'db_type' => DB_TYPE,
        ];
        require_once ROOTCWD . 'zc_install/includes/classes/class.zcDatabaseInstaller.php';
        $extendedOptions = [
            'doJsonProgressLogging' => false,
            'doJsonProgressLoggingFileName' => DEBUG_LOG_FOLDER . '/progress.json',
            'id' => 'main',
            'message' => '',
        ];

        $file = ROOTCWD . 'zc_install/sql/install/mysql_zencart.sql';
        $dbInstaller = new \zcDatabaseInstaller($options);
        $conn = $dbInstaller->getConnection();
        $error = $dbInstaller->parseSqlFile($file, $extendedOptions);
    }

    public static function runInitialSeeders()
    {
        echo 'Running Initial Seeders' . PHP_EOL;
        $runner = new SeederRunner();
        $runner->run('InitialSeeders', 'DatabaseSeeder');
    }

    public static function runCustomSeeder($seederClass)
    {
        echo 'Running Custom Seeder' . PHP_EOL;
        $runner = new SeederRunner();
        $runner->run('CustomSeeders', $seederClass);
    }

}
