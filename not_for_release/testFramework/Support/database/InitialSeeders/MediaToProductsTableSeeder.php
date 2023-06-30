<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class MediaToProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('media_to_products')->truncate();

        Capsule::table('media_to_products')->insert(array(
            0 =>
                array(
                    'media_id' => 1,
                    'product_id' => 166,
                ),
            1 =>
                array(
                    'media_id' => 2,
                    'product_id' => 169,
                ),
        ));


    }
}
