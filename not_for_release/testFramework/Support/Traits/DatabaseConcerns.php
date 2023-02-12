<?php

namespace Tests\Support\Traits;

use Illuminate\Database\Capsule\Manager as Capsule;

trait DatabaseConcerns
{
    protected $configPath = '/not_for_release/testFramework/Support/DatabaseConfigure';
    protected $user;
    protected \queryFactory $db;
    protected ?\PDO $pdoConnection;
    protected static $initialized = FALSE;

    public function setup(): void
    {
        parent::setup();
        if (!self::$initialized) {
            // Do something once here for _all_ test subclasses.
            $this->user = $this->detectUser();
            $this->root = getcwd();

            //echo "\n" . "Found User = " . $this->user . "\n";
            $this->loadDatabaseConfigures($this->detectUser());
            self::$initialized = TRUE;
        }

        if (!isset($this->databaseFixtures)) {
            return;
        }

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

        $this->db = new \queryFactory();
        $GLOBALS['db'] = $this->db;
        if (!$this->db->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, false, false)) {

        }

        $this->getPdoConnection(DB_DATABASE);
        $this->prepareDatabase();
    }

    public function tearDown() : void
    {
        foreach($this->databaseFixtures as $fixture => $tables) {
            $f = '\\Tests\\Support\\DatabaseFixtures\\' . ucfirst($fixture) . 'Fixture';
            $class = new $f($tables, $this->pdoConnection);
            $class->unloadFixture();
        }
        parent::tearDown();
    }


    protected function prepareDatabase()
    {
        foreach($this->databaseFixtures as $fixture => $tables) {
            $this->loadFixture($fixture, $tables);
        }
    }

    protected function loadFixture($fixture, $tables)
    {
        $f = '\\Tests\\Support\\DatabaseFixtures\\' . ucfirst($fixture) . 'Fixture';
        $class = new $f($tables, $this->pdoConnection);
        $class->createTable();
        $class->seeder();
    }

    public function getPdoConnection(string $dbName = null)
    {
        $dsn = 'mysql:host=' . DB_SERVER;
        if (isset($dbName)) {
            $dsn .= ';dbname=' . $dbName;
        }
        try {
            $conn = new \PDO($dsn, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
        } catch (\PDOException $e) {
            $conn = null;
        }
        $this->pdoConnection = $conn;
    }

    public function loadDatabaseConfigures($user)
    {
        $f = $this->root . $this->configPath . '/' . $user . '.configure.db.php';
        //echo "\n" . "looking for DB Config = " . $f . "\n";

        if (!file_exists( $f)) {
            echo 'Could not find DB Config' . "\n";
            die(1);
        }
        require($f);
    }
}
