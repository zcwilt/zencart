<?php
/**
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Support;

use PHPUnit\Framework\TestResult;
use Tests\Support\Traits\ConfigurationSettingsConcerns;
use Tests\Support\Traits\DiscountCouponConcerns;
use Tests\Support\Traits\LowOrderFeeConcerns;
use Tests\Support\Traits\CustomerAccountConcerns;
use Tests\Support\Traits\DatabaseConcerns;
use Tests\Support\Traits\GeneralConcerns;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Support\Traits\LogFileConcerns;

require_once __DIR__ . '/configs/runtime_config.php';

/**
 *
 */
abstract class zcFeatureTestCase extends WebTestCase
{
    use DatabaseConcerns, GeneralConcerns, CustomerAccountConcerns, ConfigurationSettingsConcerns, LogFileConcerns, LowOrderFeeConcerns, DiscountCouponConcerns;

    /**
     * @param TestResult|null $result
     * @return TestResult
     *
     * This allows us to run in full isolation mode including
     * classes, functions, and defined statements
     */
    public function run(?TestResult $result = null): TestResult
    {
        return parent::run($result);
    }

    public function setUp(): void
    {
        $this->createHttpBrowser();
        parent::setUp();
    }

    /**
     * @return void
     *
     * set some defines where necessary
     */
    public static function setUpBeforeClass(): void
    {
        self::defineFeatureTestConstants();
        self::removeLogFiles();
    }

    public function tearDown(): void
    {
        self::removeProgressFile();
        parent::tearDown();
    }

    protected static function setUpFeatureTestContext(string $context): void
    {
        $mainConfigs = self::loadConfigureFile('main');
        self::loadConfigureFile($context);

        if (!defined('TABLE_ADDRESS_BOOK')) {
            require ROOTCWD . 'includes/database_tables.php';
        }

        self::loadMigrationAndSeeders($mainConfigs);
    }

    protected static function defineFeatureTestConstants(): void
    {
        self::defineConstantIfMissing('ZENCART_TESTFRAMEWORK_RUNNING', true);
        self::defineConstantIfMissing('TESTCWD', realpath(__DIR__ . '/../') . '/');
        self::defineConstantIfMissing('ROOTCWD', realpath(__DIR__ . '/../../../') . '/');
        self::defineConstantIfMissing('TEXT_PROGRESS_FINISHED', '');
    }

    protected static function removeProgressFile(): void
    {
        $progressFile = zc_test_config_progress_file(ROOTCWD);
        if (file_exists($progressFile)) {
            unlink($progressFile);
        }
    }

    protected static function removeLogFiles(): void
    {
        $files = glob(zc_test_config_log_directory(ROOTCWD) . '/myDEBUG*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                self::moveLogFileForArtifacts($file);
                unlink($file);
            }
        }
    }

    protected static function moveLogFileForArtifacts(string $file): void
    {
        $context = 'store';
        if (str_starts_with(basename($file), 'myDEBUG-adm')) {
            $context = 'admin';
        }

        $artifactDirectory = zc_test_config_artifact_directory(ROOTCWD, $context);
        if (!is_dir($artifactDirectory)) {
            mkdir($artifactDirectory, 0777, true);
        }

        copy($file, $artifactDirectory . basename($file));
    }

    protected static function defineConstantIfMissing(string $name, mixed $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

}
