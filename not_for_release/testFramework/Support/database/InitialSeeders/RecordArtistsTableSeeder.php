<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class RecordArtistsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('record_artists')->truncate();

        Capsule::table('record_artists')->insert(array(
            0 =>
                array(
                    'artists_id' => 1,
                    'artists_image' => 'sooty.jpg',
                    'artists_name' => 'The Russ Tippins Band',
                    'date_added' => '2004-06-01 20:53:00',
                    'last_modified' => NULL,
                ),
        ));


    }
}
