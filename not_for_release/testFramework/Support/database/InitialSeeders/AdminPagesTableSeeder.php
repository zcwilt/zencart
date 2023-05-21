<?php

namespace InitialSeeders;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Seeder;

class AdminPagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        Capsule::table('admin_pages')->truncate();

        Capsule::table('admin_pages')->insert(array (
            0 =>
            array (
                'page_key' => 'configMyStore',
                'language_key' => 'BOX_CONFIGURATION_MY_STORE',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=1',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            1 =>
            array (
                'page_key' => 'configMinimumValues',
                'language_key' => 'BOX_CONFIGURATION_MINIMUM_VALUES',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=2',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            2 =>
            array (
                'page_key' => 'configMaximumValues',
                'language_key' => 'BOX_CONFIGURATION_MAXIMUM_VALUES',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=3',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            3 =>
            array (
                'page_key' => 'configImages',
                'language_key' => 'BOX_CONFIGURATION_IMAGES',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=4',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            4 =>
            array (
                'page_key' => 'configCustomerDetails',
                'language_key' => 'BOX_CONFIGURATION_CUSTOMER_DETAILS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=5',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 5,
            ),
            5 =>
            array (
                'page_key' => 'configShipping',
                'language_key' => 'BOX_CONFIGURATION_SHIPPING_PACKAGING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=7',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 6,
            ),
            6 =>
            array (
                'page_key' => 'configProductListing',
                'language_key' => 'BOX_CONFIGURATION_PRODUCT_LISTING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=8',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 7,
            ),
            7 =>
            array (
                'page_key' => 'configStock',
                'language_key' => 'BOX_CONFIGURATION_STOCK',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=9',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 8,
            ),
            8 =>
            array (
                'page_key' => 'configLogging',
                'language_key' => 'BOX_CONFIGURATION_LOGGING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=10',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 9,
            ),
            9 =>
            array (
                'page_key' => 'configEmail',
                'language_key' => 'BOX_CONFIGURATION_EMAIL_OPTIONS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=12',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 10,
            ),
            10 =>
            array (
                'page_key' => 'configAttributes',
                'language_key' => 'BOX_CONFIGURATION_ATTRIBUTE_OPTIONS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=13',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 11,
            ),
            11 =>
            array (
                'page_key' => 'configGzipCompression',
                'language_key' => 'BOX_CONFIGURATION_GZIP_COMPRESSION',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=14',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 12,
            ),
            12 =>
            array (
                'page_key' => 'configSessions',
                'language_key' => 'BOX_CONFIGURATION_SESSIONS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=15',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 13,
            ),
            13 =>
            array (
                'page_key' => 'configRegulations',
                'language_key' => 'BOX_CONFIGURATION_REGULATIONS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=11',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 14,
            ),
            14 =>
            array (
                'page_key' => 'configGvCoupons',
                'language_key' => 'BOX_CONFIGURATION_GV_COUPONS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=16',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 15,
            ),
            15 =>
            array (
                'page_key' => 'configCreditCards',
                'language_key' => 'BOX_CONFIGURATION_CREDIT_CARDS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=17',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 16,
            ),
            16 =>
            array (
                'page_key' => 'configProductInfo',
                'language_key' => 'BOX_CONFIGURATION_PRODUCT_INFO',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=18',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 17,
            ),
            17 =>
            array (
                'page_key' => 'configLayoutSettings',
                'language_key' => 'BOX_CONFIGURATION_LAYOUT_SETTINGS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=19',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 18,
            ),
            18 =>
            array (
                'page_key' => 'configWebsiteMaintenance',
                'language_key' => 'BOX_CONFIGURATION_WEBSITE_MAINTENANCE',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=20',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 19,
            ),
            19 =>
            array (
                'page_key' => 'configNewListing',
                'language_key' => 'BOX_CONFIGURATION_NEW_LISTING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=21',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 20,
            ),
            20 =>
            array (
                'page_key' => 'configFeaturedListing',
                'language_key' => 'BOX_CONFIGURATION_FEATURED_LISTING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=22',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 21,
            ),
            21 =>
            array (
                'page_key' => 'configAllListing',
                'language_key' => 'BOX_CONFIGURATION_ALL_LISTING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=23',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 22,
            ),
            22 =>
            array (
                'page_key' => 'configIndexListing',
                'language_key' => 'BOX_CONFIGURATION_INDEX_LISTING',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=24',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 23,
            ),
            23 =>
            array (
                'page_key' => 'configDefinePageStatus',
                'language_key' => 'BOX_CONFIGURATION_DEFINE_PAGE_STATUS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=25',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 24,
            ),
            24 =>
            array (
                'page_key' => 'configEzPagesSettings',
                'language_key' => 'BOX_CONFIGURATION_EZPAGES_SETTINGS',
                'main_page' => 'FILENAME_CONFIGURATION',
                'page_params' => 'gID=30',
                'menu_key' => 'configuration',
                'display_on_menu' => 'Y',
                'sort_order' => 25,
            ),
            25 =>
            array (
                'page_key' => 'categories',
                'language_key' => 'BOX_CATALOG_CATEGORY',
                'main_page' => 'FILENAME_CATEGORIES',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'N',
                'sort_order' => 18,
            ),
            26 =>
            array (
                'page_key' => 'categoriesProductListing',
                'language_key' => 'BOX_CATALOG_CATEGORIES_PRODUCTS',
                'main_page' => 'FILENAME_CATEGORY_PRODUCT_LISTING',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            27 =>
            array (
                'page_key' => 'productTypes',
                'language_key' => 'BOX_CATALOG_PRODUCT_TYPES',
                'main_page' => 'FILENAME_PRODUCT_TYPES',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            28 =>
            array (
                'page_key' => 'priceManager',
                'language_key' => 'BOX_CATALOG_PRODUCTS_PRICE_MANAGER',
                'main_page' => 'FILENAME_PRODUCTS_PRICE_MANAGER',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            29 =>
            array (
                'page_key' => 'optionNames',
                'language_key' => 'BOX_CATALOG_CATEGORIES_OPTIONS_NAME_MANAGER',
                'main_page' => 'FILENAME_OPTIONS_NAME_MANAGER',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            30 =>
            array (
                'page_key' => 'optionValues',
                'language_key' => 'BOX_CATALOG_CATEGORIES_OPTIONS_VALUES_MANAGER',
                'main_page' => 'FILENAME_OPTIONS_VALUES_MANAGER',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 5,
            ),
            31 =>
            array (
                'page_key' => 'attributes',
                'language_key' => 'BOX_CATALOG_CATEGORIES_ATTRIBUTES_CONTROLLER',
                'main_page' => 'FILENAME_ATTRIBUTES_CONTROLLER',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 6,
            ),
            32 =>
            array (
                'page_key' => 'downloads',
                'language_key' => 'BOX_CATALOG_CATEGORIES_ATTRIBUTES_DOWNLOADS_MANAGER',
                'main_page' => 'FILENAME_DOWNLOADS_MANAGER',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 7,
            ),
            33 =>
            array (
                'page_key' => 'optionNameSorter',
                'language_key' => 'BOX_CATALOG_PRODUCT_OPTIONS_NAME',
                'main_page' => 'FILENAME_PRODUCTS_OPTIONS_NAME',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 8,
            ),
            34 =>
            array (
                'page_key' => 'optionValueSorter',
                'language_key' => 'BOX_CATALOG_PRODUCT_OPTIONS_VALUES',
                'main_page' => 'FILENAME_PRODUCTS_OPTIONS_VALUES',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 9,
            ),
            35 =>
            array (
                'page_key' => 'manufacturers',
                'language_key' => 'BOX_CATALOG_MANUFACTURERS',
                'main_page' => 'FILENAME_MANUFACTURERS',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 10,
            ),
            36 =>
            array (
                'page_key' => 'reviews',
                'language_key' => 'BOX_CATALOG_REVIEWS',
                'main_page' => 'FILENAME_REVIEWS',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 11,
            ),
            37 =>
            array (
                'page_key' => 'specials',
                'language_key' => 'BOX_CATALOG_SPECIALS',
                'main_page' => 'FILENAME_SPECIALS',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 12,
            ),
            38 =>
            array (
                'page_key' => 'featured',
                'language_key' => 'BOX_CATALOG_FEATURED',
                'main_page' => 'FILENAME_FEATURED',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 13,
            ),
            39 =>
            array (
                'page_key' => 'salemaker',
                'language_key' => 'BOX_CATALOG_SALEMAKER',
                'main_page' => 'FILENAME_SALEMAKER',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 14,
            ),
            40 =>
            array (
                'page_key' => 'productsExpected',
                'language_key' => 'BOX_CATALOG_PRODUCTS_EXPECTED',
                'main_page' => 'FILENAME_PRODUCTS_EXPECTED',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 15,
            ),
            41 =>
            array (
                'page_key' => 'product',
                'language_key' => 'BOX_CATALOG_PRODUCT',
                'main_page' => 'FILENAME_PRODUCT',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'N',
                'sort_order' => 16,
            ),
            42 =>
            array (
                'page_key' => 'productsToCategories',
                'language_key' => 'BOX_CATALOG_PRODUCTS_TO_CATEGORIES',
                'main_page' => 'FILENAME_PRODUCTS_TO_CATEGORIES',
                'page_params' => '',
                'menu_key' => 'catalog',
                'display_on_menu' => 'Y',
                'sort_order' => 17,
            ),
            43 =>
            array (
                'page_key' => 'payment',
                'language_key' => 'BOX_MODULES_PAYMENT',
                'main_page' => 'FILENAME_MODULES',
                'page_params' => 'set=payment',
                'menu_key' => 'modules',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            44 =>
            array (
                'page_key' => 'shipping',
                'language_key' => 'BOX_MODULES_SHIPPING',
                'main_page' => 'FILENAME_MODULES',
                'page_params' => 'set=shipping',
                'menu_key' => 'modules',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            45 =>
            array (
                'page_key' => 'plugins',
                'language_key' => 'BOX_MODULES_PLUGINS',
                'main_page' => 'FILENAME_PLUGIN_MANAGER',
                'page_params' => '',
                'menu_key' => 'modules',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            46 =>
            array (
                'page_key' => 'orderTotal',
                'language_key' => 'BOX_MODULES_ORDER_TOTAL',
                'main_page' => 'FILENAME_MODULES',
                'page_params' => 'set=ordertotal',
                'menu_key' => 'modules',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            47 =>
            array (
                'page_key' => 'customers',
                'language_key' => 'BOX_CUSTOMERS_CUSTOMERS',
                'main_page' => 'FILENAME_CUSTOMERS',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            48 =>
            array (
                'page_key' => 'customerGroups',
                'language_key' => 'BOX_CUSTOMERS_CUSTOMER_GROUPS',
                'main_page' => 'FILENAME_CUSTOMER_GROUPS',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            49 =>
            array (
                'page_key' => 'orders',
                'language_key' => 'BOX_CUSTOMERS_ORDERS',
                'main_page' => 'FILENAME_ORDERS',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            50 =>
            array (
                'page_key' => 'groupPricing',
                'language_key' => 'BOX_CUSTOMERS_GROUP_PRICING',
                'main_page' => 'FILENAME_GROUP_PRICING',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            51 =>
            array (
                'page_key' => 'paypal',
                'language_key' => 'BOX_CUSTOMERS_PAYPAL',
                'main_page' => 'FILENAME_PAYPAL',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            52 =>
            array (
                'page_key' => 'invoice',
                'language_key' => 'BOX_CUSTOMERS_INVOICE',
                'main_page' => 'FILENAME_ORDERS_INVOICE',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'N',
                'sort_order' => 5,
            ),
            53 =>
            array (
                'page_key' => 'packingslip',
                'language_key' => 'BOX_CUSTOMERS_PACKING_SLIP',
                'main_page' => 'FILENAME_ORDERS_PACKINGSLIP',
                'page_params' => '',
                'menu_key' => 'customers',
                'display_on_menu' => 'N',
                'sort_order' => 6,
            ),
            54 =>
            array (
                'page_key' => 'countries',
                'language_key' => 'BOX_TAXES_COUNTRIES',
                'main_page' => 'FILENAME_COUNTRIES',
                'page_params' => '',
                'menu_key' => 'taxes',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            55 =>
            array (
                'page_key' => 'zones',
                'language_key' => 'BOX_TAXES_ZONES',
                'main_page' => 'FILENAME_ZONES',
                'page_params' => '',
                'menu_key' => 'taxes',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            56 =>
            array (
                'page_key' => 'geoZones',
                'language_key' => 'BOX_TAXES_GEO_ZONES',
                'main_page' => 'FILENAME_GEO_ZONES',
                'page_params' => '',
                'menu_key' => 'taxes',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            57 =>
            array (
                'page_key' => 'taxClasses',
                'language_key' => 'BOX_TAXES_TAX_CLASSES',
                'main_page' => 'FILENAME_TAX_CLASSES',
                'page_params' => '',
                'menu_key' => 'taxes',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            58 =>
            array (
                'page_key' => 'taxRates',
                'language_key' => 'BOX_TAXES_TAX_RATES',
                'main_page' => 'FILENAME_TAX_RATES',
                'page_params' => '',
                'menu_key' => 'taxes',
                'display_on_menu' => 'Y',
                'sort_order' => 5,
            ),
            59 =>
            array (
                'page_key' => 'currencies',
                'language_key' => 'BOX_LOCALIZATION_CURRENCIES',
                'main_page' => 'FILENAME_CURRENCIES',
                'page_params' => '',
                'menu_key' => 'localization',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            60 =>
            array (
                'page_key' => 'languages',
                'language_key' => 'BOX_LOCALIZATION_LANGUAGES',
                'main_page' => 'FILENAME_LANGUAGES',
                'page_params' => '',
                'menu_key' => 'localization',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            61 =>
            array (
                'page_key' => 'ordersStatus',
                'language_key' => 'BOX_LOCALIZATION_ORDERS_STATUS',
                'main_page' => 'FILENAME_ORDERS_STATUS',
                'page_params' => '',
                'menu_key' => 'localization',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            62 =>
            array (
                'page_key' => 'reportCustomers',
                'language_key' => 'BOX_REPORTS_ORDERS_TOTAL',
                'main_page' => 'FILENAME_STATS_CUSTOMERS',
                'page_params' => '',
                'menu_key' => 'reports',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            63 =>
            array (
                'page_key' => 'reportReferrals',
                'language_key' => 'BOX_REPORTS_CUSTOMERS_REFERRALS',
                'main_page' => 'FILENAME_STATS_CUSTOMERS_REFERRALS',
                'page_params' => '',
                'menu_key' => 'reports',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            64 =>
            array (
                'page_key' => 'reportLowStock',
                'language_key' => 'BOX_REPORTS_PRODUCTS_LOWSTOCK',
                'main_page' => 'FILENAME_STATS_PRODUCTS_LOWSTOCK',
                'page_params' => '',
                'menu_key' => 'reports',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            65 =>
            array (
                'page_key' => 'reportProductsSold',
                'language_key' => 'BOX_REPORTS_PRODUCTS_PURCHASED',
                'main_page' => 'FILENAME_STATS_PRODUCTS_PURCHASED',
                'page_params' => '',
                'menu_key' => 'reports',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            66 =>
            array (
                'page_key' => 'reportProductsViewed',
                'language_key' => 'BOX_REPORTS_PRODUCTS_VIEWED',
                'main_page' => 'FILENAME_STATS_PRODUCTS_VIEWED',
                'page_params' => '',
                'menu_key' => 'reports',
                'display_on_menu' => 'Y',
                'sort_order' => 5,
            ),
            67 =>
            array (
                'page_key' => 'templateSelect',
                'language_key' => 'BOX_TOOLS_TEMPLATE_SELECT',
                'main_page' => 'FILENAME_TEMPLATE_SELECT',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            68 =>
            array (
                'page_key' => 'layoutController',
                'language_key' => 'BOX_TOOLS_LAYOUT_CONTROLLER',
                'main_page' => 'FILENAME_LAYOUT_CONTROLLER',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            69 =>
            array (
                'page_key' => 'banners',
                'language_key' => 'BOX_TOOLS_BANNER_MANAGER',
                'main_page' => 'FILENAME_BANNER_MANAGER',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            70 =>
            array (
                'page_key' => 'mail',
                'language_key' => 'BOX_TOOLS_MAIL',
                'main_page' => 'FILENAME_MAIL',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            71 =>
            array (
                'page_key' => 'newsletters',
                'language_key' => 'BOX_TOOLS_NEWSLETTER_MANAGER',
                'main_page' => 'FILENAME_NEWSLETTERS',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 5,
            ),
            72 =>
            array (
                'page_key' => 'server',
                'language_key' => 'BOX_TOOLS_SERVER_INFO',
                'main_page' => 'FILENAME_SERVER_INFO',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 6,
            ),
            73 =>
            array (
                'page_key' => 'whosOnline',
                'language_key' => 'BOX_TOOLS_WHOS_ONLINE',
                'main_page' => 'FILENAME_WHOS_ONLINE',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 7,
            ),
            74 =>
            array (
                'page_key' => 'storeManager',
                'language_key' => 'BOX_TOOLS_STORE_MANAGER',
                'main_page' => 'FILENAME_STORE_MANAGER',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 9,
            ),
            75 =>
            array (
                'page_key' => 'developersToolKit',
                'language_key' => 'BOX_TOOLS_DEVELOPERS_TOOL_KIT',
                'main_page' => 'FILENAME_DEVELOPERS_TOOL_KIT',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 10,
            ),
            76 =>
            array (
                'page_key' => 'ezpages',
                'language_key' => 'BOX_TOOLS_EZPAGES',
                'main_page' => 'FILENAME_EZPAGES_ADMIN',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 11,
            ),
            77 =>
            array (
                'page_key' => 'definePagesEditor',
                'language_key' => 'BOX_TOOLS_DEFINE_PAGES_EDITOR',
                'main_page' => 'FILENAME_DEFINE_PAGES_EDITOR',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 12,
            ),
            78 =>
            array (
                'page_key' => 'sqlPatch',
                'language_key' => 'BOX_TOOLS_SQLPATCH',
                'main_page' => 'FILENAME_SQLPATCH',
                'page_params' => '',
                'menu_key' => 'tools',
                'display_on_menu' => 'Y',
                'sort_order' => 13,
            ),
            79 =>
            array (
                'page_key' => 'couponAdmin',
                'language_key' => 'BOX_COUPON_ADMIN',
                'main_page' => 'FILENAME_COUPON_ADMIN',
                'page_params' => '',
                'menu_key' => 'gv',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            80 =>
            array (
                'page_key' => 'couponRestrict',
                'language_key' => 'BOX_COUPON_RESTRICT',
                'main_page' => 'FILENAME_COUPON_RESTRICT',
                'page_params' => '',
                'menu_key' => 'gv',
                'display_on_menu' => 'N',
                'sort_order' => 1,
            ),
            81 =>
            array (
                'page_key' => 'gvQueue',
                'language_key' => 'BOX_GV_ADMIN_QUEUE',
                'main_page' => 'FILENAME_GV_QUEUE',
                'page_params' => '',
                'menu_key' => 'gv',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            82 =>
            array (
                'page_key' => 'gvMail',
                'language_key' => 'BOX_GV_ADMIN_MAIL',
                'main_page' => 'FILENAME_GV_MAIL',
                'page_params' => '',
                'menu_key' => 'gv',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            83 =>
            array (
                'page_key' => 'gvSent',
                'language_key' => 'BOX_GV_ADMIN_SENT',
                'main_page' => 'FILENAME_GV_SENT',
                'page_params' => '',
                'menu_key' => 'gv',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            84 =>
            array (
                'page_key' => 'profiles',
                'language_key' => 'BOX_ADMIN_ACCESS_PROFILES',
                'main_page' => 'FILENAME_PROFILES',
                'page_params' => '',
                'menu_key' => 'access',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            85 =>
            array (
                'page_key' => 'users',
                'language_key' => 'BOX_ADMIN_ACCESS_USERS',
                'main_page' => 'FILENAME_USERS',
                'page_params' => '',
                'menu_key' => 'access',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            86 =>
            array (
                'page_key' => 'pageRegistration',
                'language_key' => 'BOX_ADMIN_ACCESS_PAGE_REGISTRATION',
                'main_page' => 'FILENAME_ADMIN_PAGE_REGISTRATION',
                'page_params' => '',
                'menu_key' => 'access',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            87 =>
            array (
                'page_key' => 'adminlogs',
                'language_key' => 'BOX_ADMIN_ACCESS_LOGS',
                'main_page' => 'FILENAME_ADMIN_ACTIVITY',
                'page_params' => '',
                'menu_key' => 'access',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            88 =>
            array (
                'page_key' => 'recordArtists',
                'language_key' => 'BOX_CATALOG_RECORD_ARTISTS',
                'main_page' => 'FILENAME_RECORD_ARTISTS',
                'page_params' => '',
                'menu_key' => 'extras',
                'display_on_menu' => 'Y',
                'sort_order' => 1,
            ),
            89 =>
            array (
                'page_key' => 'recordCompanies',
                'language_key' => 'BOX_CATALOG_RECORD_COMPANY',
                'main_page' => 'FILENAME_RECORD_COMPANY',
                'page_params' => '',
                'menu_key' => 'extras',
                'display_on_menu' => 'Y',
                'sort_order' => 2,
            ),
            90 =>
            array (
                'page_key' => 'musicGenre',
                'language_key' => 'BOX_CATALOG_MUSIC_GENRE',
                'main_page' => 'FILENAME_MUSIC_GENRE',
                'page_params' => '',
                'menu_key' => 'extras',
                'display_on_menu' => 'Y',
                'sort_order' => 3,
            ),
            91 =>
            array (
                'page_key' => 'mediaManager',
                'language_key' => 'BOX_CATALOG_MEDIA_MANAGER',
                'main_page' => 'FILENAME_MEDIA_MANAGER',
                'page_params' => '',
                'menu_key' => 'extras',
                'display_on_menu' => 'Y',
                'sort_order' => 4,
            ),
            92 =>
            array (
                'page_key' => 'mediaTypes',
                'language_key' => 'BOX_CATALOG_MEDIA_TYPES',
                'main_page' => 'FILENAME_MEDIA_TYPES',
                'page_params' => '',
                'menu_key' => 'extras',
                'display_on_menu' => 'Y',
                'sort_order' => 5,
            ),
        ));


    }
}
