<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class LanguagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('languages')->truncate();

        Capsule::table('languages')->insert(array (
            0 =>
            array (
                'languages_id' => 1,
                'name' => 'English',
                'code' => 'en',
                'image' => 'icon.gif',
                'directory' => 'english',
                'sort_order' => 1,
            ),
        ));


    }
}
