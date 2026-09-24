<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

declare(strict_types=1);

namespace Tests\FeatureAdmin\AdminEndpoints;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\Support\InProcess\FeatureResponse;
use Tests\Support\zcInProcessFeatureTestCaseAdmin;

/**
 * admin/product_types.php renders a product-type layout setting that has a set_function by
 * building a PHP call around the stored value and eval()'ing it. The stored value must be
 * passed as inert data: it may not execute, break the generated code, or be altered on the
 * way to the form.
 *
 * Stock layout rows all use zen_cfg_select_drop_down(); one row of the General product type
 * is switched to a textarea set_function so a free-text value is routed through that eval().
 */
#[Group('parallel-candidate')]
#[Group('custom-seeder')]
#[RunTestsInSeparateProcesses]
class AdminProductTypesSetFunctionEscapingTest extends zcInProcessFeatureTestCaseAdmin
{
    private const PRODUCT_TYPE_ID = 1;
    private const LAYOUT_KEY = 'SHOW_PRODUCT_INFO_MODEL';
    private const DROP_DOWN_SET_FUNCTION = "zen_cfg_select_drop_down([['id'=>'1', 'text'=>'True'], ['id'=>'0', 'text'=>'False']], ";
    private const FORM_NAME = 'configuration';
    private const FIELD_NAME = 'configuration_value';
    private const CANARY_KEY = 'ZCTEST_PRODUCT_TYPES_EVAL_CANARY';

    /**
     * Values that are awkward inside a PHP string literal or an HTML textarea: quote
     * breakouts, variable/complex interpolation, backticks, entities and markup.
     */
    private const AWKWARD_VALUES = [
        'plain' => 'plain value',
        'single quote' => "it's",
        'double quote' => 'say "hi"',
        'ampersand' => 'Tom & Jerry',
        'pre-encoded' => 'A &amp; B',
        'markup' => '<b>bold</b>',
        'quote breakout' => "'.phpinfo().'",
        'simple interpolation' => '$db',
        'complex interpolation' => '{$db}',
        'backticks' => '`id`',
    ];

    /**
     * Values containing backslashes. The layout row is loaded through objectInfo, whose
     * zen_db_prepare_input() stripslashes() every value, so these reach the eval() without
     * their backslashes. They are still checked to render without executing anything or
     * breaking the generated code.
     */
    private const BACKSLASH_VALUES = [
        'windows path' => 'C:\new\temp',
        'trailing backslash' => 'ends with \\',
    ];

    /**
     * Admin requests run in a separate PHP process, so an executed payload is detected through
     * the database: it flips a canary configuration row that the test seeds and then inspects.
     */
    public function testStoredValueIsNotExecutedWhenTheLayoutEditFormIsRendered(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $payload = $this->interpolationPayload($this->seedCanary($db));
        $layoutId = $this->seedLayoutValue($db, $payload);

        $page = $this->visitEditFormCheckingCanary($db, $layoutId, 'The stored layout value was executed by eval().');

        $page->assertOk();
        $this->assertSame(
            $payload,
            $page->formDefaults(self::FORM_NAME)[self::FIELD_NAME] ?? null,
            'The stored layout value was not rendered literally.'
        );
    }

    public function testAwkwardValuesRenderLiterally(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $failures = [];
        foreach (self::AWKWARD_VALUES as $label => $value) {
            $layoutId = $this->seedLayoutValue($db, $value);

            $page = $this->visitAdminCommand($this->editCommand($layoutId))->assertOk();
            $rendered = $page->formDefaults(self::FORM_NAME)[self::FIELD_NAME] ?? null;
            if ($rendered !== $value) {
                $failures[$label] = ['stored' => $value, 'rendered' => $rendered];
            }
        }

        $this->assertSame([], $failures, 'These values were altered by the layout edit form.');
    }

    public function testBackslashValuesRenderWithoutExecuting(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $canaryId = $this->seedCanary($db);
        $values = self::BACKSLASH_VALUES + [
            'escaped quote breakout' => "\\'." . $this->canaryExpression($canaryId) . ".'",
            'escaped interpolation' => '\\' . $this->interpolationPayload($canaryId),
        ];

        foreach ($values as $label => $value) {
            $layoutId = $this->seedLayoutValue($db, $value);

            $page = $this->visitEditFormCheckingCanary($db, $layoutId, "The '$label' value was executed by eval().");

            $page->assertOk();
            $this->assertSame(
                trim(stripslashes($value)),
                $page->formDefaults(self::FORM_NAME)[self::FIELD_NAME] ?? null,
                "The '$label' value was not rendered as objectInfo passed it."
            );
        }
    }

    /**
     * The stock set_function for every core layout row. '0' is the second option, so a lost
     * "selected" attribute would surface as the first option's '1'.
     */
    public function testStockDropDownLayoutSettingSelectsAndSavesStoredValue(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $layoutId = $this->seedLayoutValue($db, '0', self::DROP_DOWN_SET_FUNCTION);

        $page = $this->visitAdminCommand($this->editCommand($layoutId))->assertOk();
        $this->assertSame(
            '0',
            $page->formDefaults(self::FORM_NAME)[self::FIELD_NAME] ?? null,
            'The stored drop-down value was not selected.'
        );

        $this->submitLayoutForm($page)->assertOk();

        $this->assertSame('0', $this->layoutValue($db), 'An untouched save altered the drop-down value.');
    }

    private function editCommand(int $layoutId): string
    {
        return 'product_types&ptID=' . self::PRODUCT_TYPE_ID . '&cID=' . $layoutId . '&action=layout_edit';
    }

    /**
     * Posts the rendered edit form untouched to its layout_save action.
     */
    private function submitLayoutForm(FeatureResponse $page): FeatureResponse
    {
        $action = $page->formAction(self::FORM_NAME);
        $this->assertNotNull($action, 'The layout edit form was not rendered.');

        $data = $page->formDefaults(self::FORM_NAME);
        $this->assertArrayHasKey(self::FIELD_NAME, $data, 'The layout edit form has no value field.');

        $parts = parse_url($action);
        $uri = ($parts['path'] ?? $action) . (empty($parts['query']) ? '' : '?' . $parts['query']);

        $response = $this->postAdmin($uri, $data);

        return $response->isRedirect() ? $this->followAdminRedirect($response) : $response;
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
     * Renders the edit form, checking the canary before any request failure is surfaced: an
     * executed payload may also crash the page, and the execution is the finding that matters.
     */
    private function visitEditFormCheckingCanary(\queryFactory $db, int $layoutId, string $message): FeatureResponse
    {
        try {
            $page = $this->visitAdminCommand($this->editCommand($layoutId));
        } catch (\Throwable $e) {
            $this->assertCanaryUntouched($db, $message);
            throw $e;
        }
        $this->assertCanaryUntouched($db, $message);

        return $page;
    }

    /**
     * A PHP expression that flips the canary row. product_types.php htmlspecialchars() the
     * value before the eval(), so it avoids <, >, " and & (no -> or =>) to stay intact.
     */
    private function canaryExpression(int $canaryId): string
    {
        return "zen_db_perform(TABLE_CONFIGURATION, array_combine(['configuration_value'], ['1']), 'update', 'configuration_id = " . $canaryId . "')";
    }

    /**
     * Wraps the canary expression in ${...} syntax, which runs it when interpolated into a
     * double-quoted eval()'d string.
     */
    private function interpolationPayload(int $canaryId): string
    {
        return '${' . $this->canaryExpression($canaryId) . '}';
    }

    private function seedCanary(\queryFactory $db): int
    {
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . self::CANARY_KEY . "'");
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description,
                 configuration_group_id, sort_order, date_added)
             VALUES
                ('Eval canary', '" . self::CANARY_KEY . "', '0', 'Set to 1 if a stored value is executed', 6, 999, now())"
        );

        return (int)$db->insert_ID();
    }

    private function assertCanaryUntouched(\queryFactory $db, string $message): void
    {
        $result = $db->Execute(
            "SELECT configuration_value
               FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = '" . self::CANARY_KEY . "'
              LIMIT 1"
        );
        $this->assertFalse($result->EOF, 'The canary row is missing.');
        $this->assertSame('0', $result->fields['configuration_value'], $message);
    }

    /**
     * Stores the value on the fixture layout row and returns that row's configuration_id.
     */
    private function seedLayoutValue(\queryFactory $db, string $value, string $setFunction = 'zen_cfg_textarea('): int
    {
        $db->Execute(
            "UPDATE " . TABLE_PRODUCT_TYPE_LAYOUT . "
                SET configuration_value = '" . addslashes($value) . "',
                    set_function = '" . addslashes($setFunction) . "'
              WHERE configuration_key = '" . self::LAYOUT_KEY . "'
                AND product_type_id = " . self::PRODUCT_TYPE_ID . "
              LIMIT 1"
        );
        $this->assertSame($value, $this->layoutValue($db), 'Fixture value was not stored.');

        $result = $db->Execute(
            "SELECT configuration_id
               FROM " . TABLE_PRODUCT_TYPE_LAYOUT . "
              WHERE configuration_key = '" . self::LAYOUT_KEY . "'
                AND product_type_id = " . self::PRODUCT_TYPE_ID . "
              LIMIT 1"
        );

        return (int)$result->fields['configuration_id'];
    }

    private function layoutValue(\queryFactory $db): ?string
    {
        $result = $db->Execute(
            "SELECT configuration_value
               FROM " . TABLE_PRODUCT_TYPE_LAYOUT . "
              WHERE configuration_key = '" . self::LAYOUT_KEY . "'
                AND product_type_id = " . self::PRODUCT_TYPE_ID . "
              LIMIT 1"
        );
        $this->assertFalse($result->EOF, 'The General product type layout row is missing from the test database.');

        return $result->fields['configuration_value'];
    }

    private function bootstrapLegacyDbConnection(): \queryFactory
    {
        if (!class_exists('queryFactory')) {
            require_once ROOTCWD . 'includes/classes/class.base.php';
            require_once ROOTCWD . 'includes/classes/db/' . DB_TYPE . '/query_factory.php';
        }

        $db = new \queryFactory();
        if (!defined('USE_PCONNECT')) {
            define('USE_PCONNECT', 'false');
        }

        $db->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, USE_PCONNECT, false);

        return $db;
    }
}
