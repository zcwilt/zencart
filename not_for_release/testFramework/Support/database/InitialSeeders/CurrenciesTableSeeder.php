<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('currencies')->truncate();

        Capsule::table('currencies')->insert(array (
            0 =>
            array (
                'currencies_id' => 1,
                'title' => 'US Dollar',
                'code' => 'USD',
                'symbol_left' => '$',
                'symbol_right' => '',
                'decimal_point' => '.',
                'thousands_point' => ',',
                'decimal_places' => '2',
                'value' => '1.000000',
                'last_updated' => '2023-06-21 14:08:02',
            ),
            1 =>
            array (
                'currencies_id' => 2,
                'title' => 'Euro',
                'code' => 'EUR',
                'symbol_left' => '&euro;',
                'symbol_right' => '',
                'decimal_point' => '.',
                'thousands_point' => ',',
                'decimal_places' => '2',
                'value' => '0.773000',
                'last_updated' => '2023-06-21 14:08:02',
            ),
            2 =>
            array (
                'currencies_id' => 3,
                'title' => 'GB Pound',
                'code' => 'GBP',
                'symbol_left' => '&pound;',
                'symbol_right' => '',
                'decimal_point' => '.',
                'thousands_point' => ',',
                'decimal_places' => '2',
                'value' => '0.672600',
                'last_updated' => '2023-06-21 14:08:02',
            ),
            3 =>
            array (
                'currencies_id' => 4,
                'title' => 'Canadian Dollar',
                'code' => 'CAD',
                'symbol_left' => '$',
                'symbol_right' => '',
                'decimal_point' => '.',
                'thousands_point' => ',',
                'decimal_places' => '2',
                'value' => '1.104200',
                'last_updated' => '2023-06-21 14:08:02',
            ),
            4 =>
            array (
                'currencies_id' => 5,
                'title' => 'Australian Dollar',
                'code' => 'AUD',
                'symbol_left' => '$',
                'symbol_right' => '',
                'decimal_point' => '.',
                'thousands_point' => ',',
                'decimal_places' => '2',
                'value' => '1.178900',
                'last_updated' => '2023-06-21 14:08:02',
            ),
        ));


    }
}
