<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class AdminMenusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('admin_menus')->truncate();

        Capsule::table('admin_menus')->insert(array (
            0 =>
            array (
                'menu_key' => 'configuration',
                'language_key' => 'BOX_HEADING_CONFIGURATION',
                'sort_order' => 1,
            ),
            1 =>
            array (
                'menu_key' => 'catalog',
                'language_key' => 'BOX_HEADING_CATALOG',
                'sort_order' => 2,
            ),
            2 =>
            array (
                'menu_key' => 'modules',
                'language_key' => 'BOX_HEADING_MODULES',
                'sort_order' => 3,
            ),
            3 =>
            array (
                'menu_key' => 'customers',
                'language_key' => 'BOX_HEADING_CUSTOMERS',
                'sort_order' => 4,
            ),
            4 =>
            array (
                'menu_key' => 'taxes',
                'language_key' => 'BOX_HEADING_LOCATION_AND_TAXES',
                'sort_order' => 5,
            ),
            5 =>
            array (
                'menu_key' => 'localization',
                'language_key' => 'BOX_HEADING_LOCALIZATION',
                'sort_order' => 6,
            ),
            6 =>
            array (
                'menu_key' => 'reports',
                'language_key' => 'BOX_HEADING_REPORTS',
                'sort_order' => 7,
            ),
            7 =>
            array (
                'menu_key' => 'tools',
                'language_key' => 'BOX_HEADING_TOOLS',
                'sort_order' => 8,
            ),
            8 =>
            array (
                'menu_key' => 'gv',
                'language_key' => 'BOX_HEADING_GV_ADMIN',
                'sort_order' => 9,
            ),
            9 =>
            array (
                'menu_key' => 'access',
                'language_key' => 'BOX_HEADING_ADMIN_ACCESS',
                'sort_order' => 10,
            ),
            10 =>
            array (
                'menu_key' => 'extras',
                'language_key' => 'BOX_HEADING_EXTRAS',
                'sort_order' => 11,
            ),
        ));


    }
}
