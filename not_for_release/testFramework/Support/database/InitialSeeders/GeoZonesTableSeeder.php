<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class GeoZonesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('geo_zones')->truncate();

        Capsule::table('geo_zones')->insert(array (
            0 =>
            array (
                'geo_zone_id' => 1,
                'geo_zone_name' => 'Florida',
                'geo_zone_description' => 'Florida local sales tax zone',
                'last_modified' => NULL,
                'date_added' => '2023-06-21 14:08:02',
            ),
        ));


    }
}
