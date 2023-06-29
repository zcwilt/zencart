<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class MediaTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('media_types')->truncate();

        Capsule::table('media_types')->insert(array(
            0 =>
                array(
                    'type_ext' => '.mp3',
                    'type_id' => 1,
                    'type_name' => 'MP3',
                ),
        ));


    }
}
