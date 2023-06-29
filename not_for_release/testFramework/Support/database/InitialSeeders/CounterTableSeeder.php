<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class CounterTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('counter')->truncate();

        Capsule::table('counter')->insert(array(
            0 =>
                array(
                    'counter' => 1,
                    'startdate' => '20230629',
                ),
        ));


    }
}
