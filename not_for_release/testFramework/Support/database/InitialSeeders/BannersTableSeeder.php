<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class BannersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('banners')->truncate();

        Capsule::table('banners')->insert(array (
            0 =>
            array (
                'banners_id' => 1,
                'banners_title' => 'Zen Cart',
                'banners_url' => 'https://www.zen-cart.com',
                'banners_image' => 'banners/zencart_468_60_02.gif',
                'banners_group' => 'Wide-Banners',
                'banners_html_text' => '',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2004-01-11 20:59:12',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 0,
            ),
            1 =>
            array (
                'banners_id' => 2,
                'banners_title' => 'Zen Cart the art of e-commerce',
                'banners_url' => 'https://www.zen-cart.com',
                'banners_image' => 'banners/125zen_logo.gif',
                'banners_group' => 'SideBox-Banners',
                'banners_html_text' => '',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2004-01-11 20:59:12',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 0,
            ),
            2 =>
            array (
                'banners_id' => 3,
                'banners_title' => 'Zen Cart the art of e-commerce',
                'banners_url' => 'https://www.zen-cart.com',
                'banners_image' => 'banners/125x125_zen_logo.gif',
                'banners_group' => 'SideBox-Banners',
                'banners_html_text' => '',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2004-01-11 20:59:12',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 0,
            ),
            3 =>
            array (
                'banners_id' => 4,
                'banners_title' => 'if you have to think ... you haven\'t been Zenned!',
                'banners_url' => 'https://www.zen-cart.com',
                'banners_image' => 'banners/think_anim.gif',
                'banners_group' => 'Wide-Banners',
                'banners_html_text' => '',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2004-01-12 20:53:18',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 0,
            ),
            4 =>
            array (
                'banners_id' => 5,
                'banners_title' => 'Zen Cart the art of e-commerce',
                'banners_url' => 'https://www.zen-cart.com',
                'banners_image' => 'banners/bw_zen_88wide.gif',
                'banners_group' => 'BannersAll',
                'banners_html_text' => '',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2005-05-13 10:54:38',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 10,
            ),
            5 =>
            array (
                'banners_id' => 6,
                'banners_title' => 'Zen Cart Certified Services',
                'banners_url' => 'https://www.zen-cart.com',
                'banners_image' => '',
                'banners_group' => 'Wide-Banners',
                'banners_html_text' => '<script><!--//<![CDATA[
var loc = \'//pan.zen-cart.com/display/group/1/\';
var rd = Math.floor(Math.random()*99999999999);
document.write ("<scr"+"ipt src=\'"+loc);
document.write (\'?rd=\' + rd);
document.write ("\'></scr"+"ipt>");
//]]>--></script>',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2004-01-11 20:59:12',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 0,
            ),
            6 =>
            array (
                'banners_id' => 7,
                'banners_title' => 'Credit Card Processing',
                'banners_url' => 'https://www.zen-cart.com/partners/square_promo',
                'banners_image' => 'banners/cardsvcs_468x60.gif',
                'banners_group' => 'Wide-Banners',
                'banners_html_text' => '',
                'expires_impressions' => 0,
                'expires_date' => NULL,
                'date_scheduled' => NULL,
                'date_added' => '2005-05-13 10:54:38',
                'date_status_change' => NULL,
                'status' => 1,
                'banners_open_new_windows' => 1,
                'banners_on_ssl' => 1,
                'banners_sort_order' => 0,
            ),
        ));


    }
}
