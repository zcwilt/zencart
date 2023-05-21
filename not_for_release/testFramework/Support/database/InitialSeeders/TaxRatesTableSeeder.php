<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class TaxRatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('tax_rates')->truncate();

        Capsule::table('tax_rates')->insert(array (
            0 =>
            array (
                'tax_rates_id' => 1,
                'tax_zone_id' => 1,
                'tax_class_id' => 1,
                'tax_priority' => 1,
                'tax_rate' => '7.0000',
                'tax_description' => 'FL TAX 7.0%',
                'last_modified' => '2023-06-21 14:08:02',
                'date_added' => '2023-06-21 14:08:02',
            ),
        ));


    }
}
