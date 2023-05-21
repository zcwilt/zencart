<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class TaxClassTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('tax_class')->truncate();

        Capsule::table('tax_class')->insert(array (
            0 =>
            array (
                'tax_class_id' => 1,
                'tax_class_title' => 'Taxable Goods',
                'tax_class_description' => 'The following types of products are included: non-food, services, etc',
                'last_modified' => NULL,
                'date_added' => '2023-06-21 14:08:02',
            ),
        ));


    }
}
