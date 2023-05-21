<?php

namespace Tests\Support\Traits;

use App\Services\MigrationsRunner;
use App\Services\SeederRunner;
use Illuminate\Database\Capsule\Manager as Capsule;

trait DatabaseConcerns
{
    public function databaseSetup(): void
    {
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
    }

    public function runMigrations()
    {
        $runner = new MigrationsRunner(ROOTCWD . 'not_for_release/testFramework/Support/database/migrations/');
        $runner->run();
    }

    public function runInitialSeeders()
    {
        $runner = new SeederRunner();
        $runner->run();
    }

}
