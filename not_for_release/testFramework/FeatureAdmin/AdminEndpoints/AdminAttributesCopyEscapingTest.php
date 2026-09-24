<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

declare(strict_types=1);

namespace Tests\FeatureAdmin\AdminEndpoints;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\Support\Database\TestDb;
use Tests\Support\InProcess\FeatureResponse;
use Tests\Support\zcInProcessFeatureTestCaseAdmin;

/**
 * zen_copy_products_attributes() rebuilds each source attribute row as a new INSERT or UPDATE
 * on the target product. The values it copies come from the database, so they must be escaped
 * and numeric columns must survive without losing precision.
 *
 * Driven through the attributes controller's "copy attributes to another product" action.
 */
#[Group('parallel-candidate')]
#[Group('custom-seeder')]
#[RunTestsInSeparateProcesses]
class AdminAttributesCopyEscapingTest extends zcInProcessFeatureTestCaseAdmin
{
    private const SOURCE_ID = 990001;
    private const TARGET_ID = 990002;
    private const OPTION_ID = 990001;
    private const OPTION_VALUE_ID = 990001;
    private const LANGUAGE_ID = 1;

    /**
     * String columns, each seeded with a value that breaks an unescaped SQL literal.
     */
    private const AWKWARD_STRINGS = [
        'attributes_image' => "attributes/o'neill.jpg",
        'options_values_price_w' => "5'x",
        'attributes_qty_prices' => "1:10,5:'8",
        'attributes_qty_prices_onetime' => 'a\\b,1:\\\'2',
    ];

    /**
     * Decimal(15,4) values; the first two carry more significant digits than PHP prints for a
     * float, so a (float) round-trip would change them.
     */
    private const DECIMALS = [
        'options_values_price' => '12345678901.2345',
        'attributes_price_factor' => '99999999999.9999',
        'attributes_price_onetime' => '0.0001',
        'attributes_price_factor_offset' => '-12.3456',
        'attributes_price_factor_onetime' => '1.5000',
        'attributes_price_factor_onetime_offset' => '0.0000',
        'attributes_price_words' => '0.2500',
        'attributes_price_letters' => '0.0100',
    ];

    public function testAwkwardStringsAreCopiedVerbatimOnInsert(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $this->seedAttribute(self::SOURCE_ID, self::AWKWARD_STRINGS);

        $this->copyAttributes('copy_attributes_delete');

        $copied = $this->attributeRow(self::TARGET_ID);
        $this->assertNotNull($copied, 'The attribute was not copied to the target product.');
        foreach (self::AWKWARD_STRINGS as $column => $value) {
            $this->assertSame($value, $copied[$column], "Column '$column' was altered by the copy.");
        }
    }

    public function testAwkwardStringsAreCopiedVerbatimOnUpdate(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $this->seedAttribute(self::SOURCE_ID, self::AWKWARD_STRINGS);
        $this->seedAttribute(self::TARGET_ID, ['attributes_image' => 'old.jpg', 'options_values_price_w' => '0']);

        $this->copyAttributes('copy_attributes_update');

        $this->assertSame(1, $this->attributeCount(self::TARGET_ID), 'The update path inserted a duplicate row.');
        $copied = $this->attributeRow(self::TARGET_ID);
        foreach (self::AWKWARD_STRINGS as $column => $value) {
            $this->assertSame($value, $copied[$column], "Column '$column' was altered by the update.");
        }
    }

    /**
     * A stored value that would close its SQL literal and set another column must be written
     * back as the same literal text, leaving the neighbouring column alone.
     */
    public function testInjectionPayloadIsStoredLiterally(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $payload = "x', attributes_required='1";
        $this->seedAttribute(self::SOURCE_ID, ['attributes_image' => $payload, 'attributes_required' => 0]);
        $this->seedAttribute(self::TARGET_ID, ['attributes_required' => 0]);

        $this->copyAttributes('copy_attributes_update');

        $copied = $this->attributeRow(self::TARGET_ID);
        $this->assertSame($payload, $copied['attributes_image']);
        $this->assertSame('0', (string)$copied['attributes_required'], 'The payload changed another column.');
    }

    /**
     * Decimal values are passed through as strings, not round-tripped through a PHP float.
     */
    public function testDecimalValuesKeepFullPrecision(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $this->seedAttribute(self::SOURCE_ID, self::DECIMALS + ['products_attributes_weight' => 1.5]);

        $this->copyAttributes('copy_attributes_delete');

        $copied = $this->attributeRow(self::TARGET_ID);
        $this->assertNotNull($copied, 'The attribute was not copied to the target product.');
        foreach (self::DECIMALS as $column => $value) {
            $this->assertSame($value, $copied[$column], "Column '$column' lost precision in the copy.");
        }
        $this->assertEquals(1.5, (float)$copied['products_attributes_weight']);
    }

    public function testDecimalValuesKeepFullPrecisionOnUpdate(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $this->seedAttribute(self::SOURCE_ID, self::DECIMALS);
        $this->seedAttribute(self::TARGET_ID);

        $this->copyAttributes('copy_attributes_update');

        $copied = $this->attributeRow(self::TARGET_ID);
        foreach (self::DECIMALS as $column => $value) {
            $this->assertSame($value, $copied[$column], "Column '$column' lost precision in the update.");
        }
    }

    /**
     * Nullable string columns have always been copied as empty strings; the copy must not fail.
     */
    public function testNullColumnsCopyAsEmptyStrings(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $this->seedAttribute(self::SOURCE_ID, [
            'attributes_image' => null,
            'attributes_qty_prices' => null,
            'attributes_qty_prices_onetime' => null,
        ]);

        $this->copyAttributes('copy_attributes_delete');

        $copied = $this->attributeRow(self::TARGET_ID);
        $this->assertNotNull($copied, 'The attribute was not copied to the target product.');
        $this->assertSame('', $copied['attributes_image']);
        $this->assertSame('', $copied['attributes_qty_prices']);
        $this->assertSame('', $copied['attributes_qty_prices_onetime']);
    }

    public function testIgnoreModeLeavesExistingTargetAttributeUnchanged(): void
    {
        $this->loginToAdmin();
        $this->seedProducts();
        $this->seedAttribute(self::SOURCE_ID, self::AWKWARD_STRINGS);
        $this->seedAttribute(self::TARGET_ID, ['attributes_image' => 'keep.jpg']);

        $this->copyAttributes('copy_attributes_ignore');

        $this->assertSame('keep.jpg', $this->attributeRow(self::TARGET_ID)['attributes_image']);
    }

    private function copyAttributes(string $mode): FeatureResponse
    {
        $page = $this->visitAdminCommand(
            'attributes_controller&products_filter=' . self::SOURCE_ID . '&current_category_id=0'
        )->assertOk();
        $securityToken = $page->securityToken();
        $this->assertNotNull($securityToken, 'The attributes controller did not render a security token.');

        return $this->postAdmin(
            '/admin/index.php?cmd=attributes_controller&action=update_attributes_copy_to_product'
                . '&products_filter=' . self::SOURCE_ID . '&current_category_id=0',
            [
                'securityToken' => $securityToken,
                'products_filter' => (string)self::SOURCE_ID,
                'products_id' => (string)self::SOURCE_ID,
                'products_update_id' => (string)self::TARGET_ID,
                'current_category_id' => '0',
                'copy_attributes' => $mode,
            ]
        );
    }

    private function loginToAdmin(): void
    {
        $this->runCustomSeeder('StoreWizardSeeder');

        $this->submitAdminLogin([
            'admin_name' => 'Admin',
            'admin_pass' => 'password',
        ])->assertOk()
            ->assertSee('Admin Home');
    }

    /**
     * Two bare products and one option name/value, removing anything left from an earlier run.
     */
    private function seedProducts(): void
    {
        $pdo = TestDb::pdo();
        $ids = self::SOURCE_ID . ', ' . self::TARGET_ID;
        $pdo->exec('DELETE FROM ' . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id IN ($ids)");
        $pdo->exec('DELETE FROM ' . TABLE_PRODUCTS_DESCRIPTION . " WHERE products_id IN ($ids)");
        $pdo->exec('DELETE FROM ' . TABLE_PRODUCTS . " WHERE products_id IN ($ids)");
        $pdo->exec('DELETE FROM ' . TABLE_PRODUCTS_OPTIONS . ' WHERE products_options_id = ' . self::OPTION_ID);
        $pdo->exec('DELETE FROM ' . TABLE_PRODUCTS_OPTIONS_VALUES . ' WHERE products_options_values_id = ' . self::OPTION_VALUE_ID);

        foreach ([self::SOURCE_ID => 'Copy source', self::TARGET_ID => 'Copy target'] as $productId => $name) {
            TestDb::insert(TABLE_PRODUCTS, [
                'products_id' => $productId,
                'products_type' => 1,
                'products_status' => 1,
                'products_date_added' => date('Y-m-d H:i:s'),
            ]);
            TestDb::insert(TABLE_PRODUCTS_DESCRIPTION, [
                'products_id' => $productId,
                'language_id' => self::LANGUAGE_ID,
                'products_name' => $name,
            ]);
        }

        TestDb::insert(TABLE_PRODUCTS_OPTIONS, [
            'products_options_id' => self::OPTION_ID,
            'language_id' => self::LANGUAGE_ID,
            'products_options_name' => 'Copy test option',
            'products_options_type' => 0,
        ]);
        TestDb::insert(TABLE_PRODUCTS_OPTIONS_VALUES, [
            'products_options_values_id' => self::OPTION_VALUE_ID,
            'language_id' => self::LANGUAGE_ID,
            'products_options_values_name' => 'Copy test value',
        ]);
    }

    private function seedAttribute(int $productId, array $values = []): void
    {
        TestDb::insert(TABLE_PRODUCTS_ATTRIBUTES, [
            'products_id' => $productId,
            'options_id' => self::OPTION_ID,
            'options_values_id' => self::OPTION_VALUE_ID,
        ] + $values);
    }

    private function attributeRow(int $productId): ?array
    {
        return TestDb::selectOne(
            'SELECT * FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' WHERE products_id = :products_id LIMIT 1',
            [':products_id' => $productId]
        );
    }

    private function attributeCount(int $productId): int
    {
        return (int)TestDb::selectValue(
            'SELECT COUNT(*) FROM ' . TABLE_PRODUCTS_ATTRIBUTES . ' WHERE products_id = :products_id',
            [':products_id' => $productId]
        );
    }
}
