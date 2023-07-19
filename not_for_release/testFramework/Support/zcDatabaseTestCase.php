<?php
/**
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Support;

use Tests\Support\Traits\DatabaseConcerns;
use Tests\Support\Traits\GeneralConcerns;

/**
 *
 */
abstract class zcDatabaseTestCase extends zcFeatureTestCase
{
    use DatabaseConcerns, GeneralConcerns;

    public function setup() : void
    {
        if (!defined('DIR_FS_CATALOG')) {
            define('DIR_FS_CATALOG', realpath(__DIR__ . '/../../../') . '/');
        }
        if (!defined('DIR_FS_INCLUDES')) {
            define('DIR_FS_INCLUDES', DIR_FS_CATALOG . 'includes/');
        }

        require_once(DIR_FS_INCLUDES . 'database_tables.php');
        parent::setUp();
    }

    /**
     * @return void
     *
     * set some defines where necessary
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadConfigureFile('store');
        self::loadMigrationAndSeeders();
    }

    public function tearDown(): void
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
    }
}
