<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class QueryBuilderTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('query_builder')->truncate();

        Capsule::table('query_builder')->insert(array (
            0 =>
            array (
                'query_id' => 1,
                'query_category' => 'email',
                'query_name' => 'All Customers',
            'query_description' => 'Returns all customers name and email address for sending mass emails (ie: for newsletters, coupons, GVs, messages, etc).',
                'query_string' => 'select customers_email_address, customers_firstname, customers_lastname from TABLE_CUSTOMERS order by customers_lastname, customers_firstname, customers_email_address',
                'query_keys_list' => '',
            ),
            1 =>
            array (
                'query_id' => 2,
                'query_category' => 'email,newsletters',
                'query_name' => 'All Newsletter Subscribers',
                'query_description' => 'Returns name and email address of newsletter subscribers',
                'query_string' => 'select customers_firstname, customers_lastname, customers_email_address from TABLE_CUSTOMERS where customers_newsletter = \'1\'',
                'query_keys_list' => '',
            ),
            2 =>
            array (
                'query_id' => 3,
                'query_category' => 'email,newsletters',
            'query_name' => 'Customers Dormant for 3+ months (Subscribers)',
                'query_description' => 'Subscribers who HAVE purchased something, but have NOT purchased for at least three months.',
            'query_string' => 'select max(o.date_purchased) as date_purchased, c.customers_email_address, c.customers_lastname, c.customers_firstname from TABLE_CUSTOMERS c, TABLE_ORDERS o WHERE c.customers_id = o.customers_id AND c.customers_newsletter = 1 GROUP BY c.customers_email_address, c.customers_lastname, c.customers_firstname HAVING max(o.date_purchased) <= subdate(now(),INTERVAL 3 MONTH) ORDER BY c.customers_lastname, c.customers_firstname ASC',
                'query_keys_list' => '',
            ),
            3 =>
            array (
                'query_id' => 4,
                'query_category' => 'email,newsletters',
            'query_name' => 'Active customers in past 3 months (Subscribers)',
            'query_description' => 'Newsletter subscribers who are also active customers (purchased something) in last 3 months.',
            'query_string' => 'select c.customers_email_address, c.customers_lastname, c.customers_firstname from TABLE_CUSTOMERS c, TABLE_ORDERS o where c.customers_newsletter = \'1\' AND c.customers_id = o.customers_id and o.date_purchased > subdate(now(),INTERVAL 3 MONTH) GROUP BY c.customers_email_address, c.customers_lastname, c.customers_firstname order by c.customers_lastname, c.customers_firstname ASC',
                'query_keys_list' => '',
            ),
            4 =>
            array (
                'query_id' => 5,
                'query_category' => 'email,newsletters',
            'query_name' => 'Active customers in past 3 months (Regardless of subscription status)',
            'query_description' => 'All active customers (purchased something) in last 3 months, ignoring newsletter-subscription status.',
            'query_string' => 'select c.customers_email_address, c.customers_lastname, c.customers_firstname from TABLE_CUSTOMERS c, TABLE_ORDERS o WHERE c.customers_id = o.customers_id and o.date_purchased > subdate(now(),INTERVAL 3 MONTH) GROUP BY c.customers_email_address, c.customers_lastname, c.customers_firstname order by c.customers_lastname, c.customers_firstname ASC',
                'query_keys_list' => '',
            ),
            5 =>
            array (
                'query_id' => 6,
                'query_category' => 'email,newsletters',
                'query_name' => 'Administrator',
                'query_description' => 'Just the email account of the current administrator',
                'query_string' => 'select \'ADMIN\' as customers_firstname, admin_name as customers_lastname, admin_email as customers_email_address from TABLE_ADMIN where admin_id = $SESSION:admin_id',
                'query_keys_list' => '',
            ),
            6 =>
            array (
                'query_id' => 7,
                'query_category' => 'email,newsletters',
                'query_name' => 'Customers who have never completed a purchase',
                'query_description' => 'For sending newsletter to all customers who registered but have never completed a purchase',
                'query_string' => 'SELECT DISTINCT c.customers_email_address as customers_email_address, c.customers_lastname as customers_lastname, c.customers_firstname as customers_firstname FROM TABLE_CUSTOMERS c LEFT JOIN  TABLE_ORDERS o ON c.customers_id=o.customers_id WHERE o.date_purchased IS NULL',
                'query_keys_list' => '',
            ),
        ));


    }
}
