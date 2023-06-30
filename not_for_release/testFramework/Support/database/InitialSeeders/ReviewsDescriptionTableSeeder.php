<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class ReviewsDescriptionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('reviews_description')->truncate();

        Capsule::table('reviews_description')->insert(array(
            0 =>
                array(
                    'languages_id' => 1,
                    'reviews_id' => 1,
                    'reviews_text' => 'This really is a very funny but old movie!',
                ),
        ));


    }
}
