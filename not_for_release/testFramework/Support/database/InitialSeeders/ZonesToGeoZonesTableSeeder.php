<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class ZonesToGeoZonesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('zones_to_geo_zones')->truncate();

        Capsule::table('zones_to_geo_zones')->insert(array (
            0 =>
            array (
                'association_id' => 1,
                'zone_country_id' => 223,
                'zone_id' => 18,
                'geo_zone_id' => 1,
                'last_modified' => NULL,
                'date_added' => '2023-06-21 14:08:02',
            ),
        ));


    }
}
