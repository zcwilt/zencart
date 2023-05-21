<?php

namespace App\Services;

use InitialSeeders\DatabaseSeeder;

class SeederRunner
{

    public function  run()
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

    }
}
