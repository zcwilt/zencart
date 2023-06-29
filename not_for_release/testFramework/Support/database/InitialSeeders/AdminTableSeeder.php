<?php

namespace InitialSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Capsule\Manager as Capsule;

class AdminTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('admin')->truncate();

        Capsule::table('admin')->insert(array (
            0 =>
                array (
                    'admin_id' => 1,
                    'admin_name' => 'Admin',
                    'admin_email' => 'admin@localhost',
                    'admin_profile' => 1,
                    'admin_pass' => password_hash('password', PASSWORD_DEFAULT),
                    'prev_pass1' => '',
                    'prev_pass2' => '',
                    'prev_pass3' => '',
                    'pwd_last_change_date' => '2023-06-21 14:08:02',
                    'reset_token' => '',
                    'last_modified' => '2023-06-21 14:08:02',
                    'last_login_date' => '2023-06-21 14:08:02',
                    'last_login_ip' => '',
                    'failed_logins' => 0,
                    'lockout_expires' => 0,
                    'last_failed_attempt' => '0001-01-01 00:00:00',
                    'last_failed_ip' => '',
                ),
        ));



    }
}
