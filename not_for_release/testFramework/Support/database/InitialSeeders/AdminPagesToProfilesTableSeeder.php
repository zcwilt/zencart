<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class AdminPagesToProfilesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('admin_pages_to_profiles')->truncate();

        Capsule::table('admin_pages_to_profiles')->insert(array (
            0 =>
            array (
                'profile_id' => 2,
                'page_key' => 'currencies',
            ),
            1 =>
            array (
                'profile_id' => 2,
                'page_key' => 'customers',
            ),
            2 =>
            array (
                'profile_id' => 2,
                'page_key' => 'gvMail',
            ),
            3 =>
            array (
                'profile_id' => 2,
                'page_key' => 'gvQueue',
            ),
            4 =>
            array (
                'profile_id' => 2,
                'page_key' => 'gvSent',
            ),
            5 =>
            array (
                'profile_id' => 2,
                'page_key' => 'invoice',
            ),
            6 =>
            array (
                'profile_id' => 2,
                'page_key' => 'mail',
            ),
            7 =>
            array (
                'profile_id' => 2,
                'page_key' => 'orders',
            ),
            8 =>
            array (
                'profile_id' => 2,
                'page_key' => 'packingslip',
            ),
            9 =>
            array (
                'profile_id' => 2,
                'page_key' => 'paypal',
            ),
            10 =>
            array (
                'profile_id' => 2,
                'page_key' => 'reportCustomers',
            ),
            11 =>
            array (
                'profile_id' => 2,
                'page_key' => 'reportLowStock',
            ),
            12 =>
            array (
                'profile_id' => 2,
                'page_key' => 'reportProductsSold',
            ),
            13 =>
            array (
                'profile_id' => 2,
                'page_key' => 'reportProductsViewed',
            ),
            14 =>
            array (
                'profile_id' => 2,
                'page_key' => 'reportReferrals',
            ),
            15 =>
            array (
                'profile_id' => 2,
                'page_key' => 'whosOnline',
            ),
        ));


    }
}
