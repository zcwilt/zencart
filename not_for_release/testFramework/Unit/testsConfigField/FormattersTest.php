<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsConfigField;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\Support\zcUnitTestCase;
use Zencart\ConfigField\Formatters\PasswordDisplayFormatter;

/**
 * Covers the one formatter adapter whose wrapped zen_get_*()/zen_cfg_*() function
 * needs no database access. The remaining formatters (country/zone/order-status/
 * tax-class lookups, currencies->format) all require DB access and are exercised
 * by the FeatureAdmin functional tests instead.
 */
#[RunTestsInSeparateProcesses]
class FormattersTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'general.php';
    }

    public function testPasswordDisplayFormatterMatchesLegacyFunctionAndIsSensitive(): void
    {
        $formatter = new PasswordDisplayFormatter();
        $this->assertSame(
            zen_cfg_password_display('a-secret-value'),
            $formatter->format('a-secret-value')
        );
        $this->assertTrue($formatter->isSensitive());
    }

    public function testPasswordDisplayFormatterCapsAtSixteenAsterisks(): void
    {
        $formatter = new PasswordDisplayFormatter();
        $this->assertSame(str_repeat('*', 16), $formatter->format(str_repeat('x', 40)));
    }
}
