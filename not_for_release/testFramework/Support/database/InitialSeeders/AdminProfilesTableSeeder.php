<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminProfilesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('admin_profiles')->truncate();

        Capsule::table('admin_profiles')->insert(array(
            0 =>
                array(
                    'profile_id' => 2,
                    'profile_name' => 'Order Processing',
                ),
            1 =>
                array(
                    'profile_id' => 1,
                    'profile_name' => 'Superuser',
                ),
        ));


    }
}
