<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class ProductsOptionsValuesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('products_options_values')->truncate();

        Capsule::table('products_options_values')->insert(array (
            0 =>
            array (
                'products_options_values_id' => 0,
                'language_id' => 1,
                'products_options_values_name' => 'TEXT',
                'products_options_values_sort_order' => 0,
            ),
        ));


    }
}
