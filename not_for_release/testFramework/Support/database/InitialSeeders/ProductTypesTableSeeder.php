<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class ProductTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('product_types')->truncate();

        Capsule::table('product_types')->insert(array (
            0 =>
            array (
                'type_id' => 1,
                'type_name' => 'Product - General',
                'type_handler' => 'product',
                'type_master_type' => 1,
                'allow_add_to_cart' => 'Y',
                'default_image' => '',
                'date_added' => '2023-06-21 14:08:02',
                'last_modified' => '2023-06-21 14:08:02',
            ),
            1 =>
            array (
                'type_id' => 2,
                'type_name' => 'Product - Music',
                'type_handler' => 'product_music',
                'type_master_type' => 1,
                'allow_add_to_cart' => 'Y',
                'default_image' => '',
                'date_added' => '2023-06-21 14:08:02',
                'last_modified' => '2023-06-21 14:08:02',
            ),
            2 =>
            array (
                'type_id' => 3,
                'type_name' => 'Document - General',
                'type_handler' => 'document_general',
                'type_master_type' => 3,
                'allow_add_to_cart' => 'N',
                'default_image' => '',
                'date_added' => '2023-06-21 14:08:02',
                'last_modified' => '2023-06-21 14:08:02',
            ),
            3 =>
            array (
                'type_id' => 4,
                'type_name' => 'Document - Product',
                'type_handler' => 'document_product',
                'type_master_type' => 3,
                'allow_add_to_cart' => 'Y',
                'default_image' => '',
                'date_added' => '2023-06-21 14:08:02',
                'last_modified' => '2023-06-21 14:08:02',
            ),
            4 =>
            array (
                'type_id' => 5,
                'type_name' => 'Product - Free Shipping',
                'type_handler' => 'product_free_shipping',
                'type_master_type' => 1,
                'allow_add_to_cart' => 'Y',
                'default_image' => '',
                'date_added' => '2023-06-21 14:08:02',
                'last_modified' => '2023-06-21 14:08:02',
            ),
        ));


    }
}
