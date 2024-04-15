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
abstract class zcFeatureTestCaseStore extends zcFeatureTestCase
{
    use DatabaseConcerns, GeneralConcerns;
    protected $context = 'store';
    /**
     * @return void
     *
     * set some defines where necessary
     */
    public function setUp(): void
    {
        parent::setUp();
    }
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $mainConfigs = self::loadConfigureFile('main');
        self::loadConfigureFile('store');
        if (!defined('TABLE_ADDRESS_BOOK')) {
            require DIR_FS_CATALOG . 'includes/database_tables.php';
        }
        require_once(ROOTCWD . 'includes/defined_paths.php');
        self::loadMigrationAndSeeders($mainConfigs);
    }

    public function tearDown(): void
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
    }
}
