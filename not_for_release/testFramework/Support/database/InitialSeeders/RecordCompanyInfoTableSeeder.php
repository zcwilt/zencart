<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class RecordCompanyInfoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('record_company_info')->truncate();

        Capsule::table('record_company_info')->insert(array(
            0 =>
                array(
                    'date_last_click' => NULL,
                    'languages_id' => 1,
                    'record_company_id' => 1,
                    'record_company_url' => 'www.hmvgroup.com',
                    'url_clicked' => 0,
                ),
        ));


    }
}
