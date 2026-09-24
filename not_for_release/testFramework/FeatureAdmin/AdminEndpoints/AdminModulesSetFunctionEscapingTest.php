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
 * admin/modules.php renders each module setting that has a set_function by building a PHP
 * call around the stored value and eval()'ing it. The stored value must be passed as inert
 * data: it may not execute, break the generated code, or be altered on the way to the form.
 *
 * Money Order is installed by default; its free-text "Make Payable to" key is switched to a
 * textarea set_function so the stored value is routed through that eval().
 */
#[Group('parallel-candidate')]
#[Group('custom-seeder')]
#[RunTestsInSeparateProcesses]
class AdminModulesSetFunctionEscapingTest extends zcInProcessFeatureTestCaseAdmin
{
    private const MODULE_KEY = 'MODULE_PAYMENT_MONEYORDER_PAYTO';
    private const FIELD_NAME = 'configuration[' . self::MODULE_KEY . ']';
    private const EDIT_COMMAND = 'modules&set=payment&module=moneyorder&action=edit';
    private const SENTINEL_DATE = '2001-02-03 04:05:06';
    private const CANARY_KEY = 'ZCTEST_MODULES_EVAL_CANARY';

    /**
     * Values that are awkward inside a PHP string literal: quote breakouts, variable/complex
     * interpolation and backticks. None contain "=" so the admin request sanitizer leaves
     * them alone when the form is posted back.
     */
    private const AWKWARD_VALUES = [
        'plain' => 'plain value',
        'single quote' => "it's",
        'double quote' => 'say "hi"',
        'ampersand' => 'Tom & Jerry',
        'quote breakout' => "'.phpinfo().'",
        'simple interpolation' => '$db',
        'complex interpolation' => '{$db}',
        'backticks' => '`id`',
    ];

    /**
     * Values containing backslashes. modules.php loads the module's keys through objectInfo,
     * whose zen_db_prepare_input() stripslashes() every value, so these reach the eval()
     * without their backslashes and do not round-trip. They are still checked to render
     * without executing anything or breaking the generated code.
     */
    private const BACKSLASH_VALUES = [
        'windows path' => 'C:\new\temp',
        'trailing backslash' => 'ends with \\',
    ];

    /**
     * Admin requests run in a separate PHP process, so an executed payload is detected through
     * the database: it flips a canary configuration row that the test seeds and then inspects.
     */
    public function testStoredValueIsNotExecutedWhenTheEditFormIsRendered(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $payload = $this->interpolationPayload($this->seedCanary($db));
        $this->seedModuleValue($db, $payload);

        $page = $this->visitAdminCommand(self::EDIT_COMMAND);

        $this->assertCanaryUntouched($db, 'The stored configuration value was executed by eval().');
        $page->assertOk();
        $this->assertSame(
            $payload,
            $page->formDefaults('modules')[self::FIELD_NAME] ?? null,
            'The stored configuration value was not rendered literally.'
        );
    }

    public function testAwkwardValuesRenderLiterallyAndSurviveAnUntouchedSave(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $failures = [];
        foreach (self::AWKWARD_VALUES as $label => $value) {
            $this->seedModuleValue($db, $value);

            $page = $this->visitAdminCommand(self::EDIT_COMMAND)->assertOk();
            $rendered = $page->formDefaults('modules')[self::FIELD_NAME] ?? null;
            if ($rendered !== $value) {
                $failures[$label] = ['stored' => $value, 'rendered' => $rendered];
                continue;
            }

            $this->submitModulesForm($page)->assertOk();

            $saved = $this->moduleValue($db);
            if ($saved !== $value) {
                $failures[$label] = ['stored' => $value, 'saved' => $saved];
            }
        }

        $this->assertSame([], $failures, 'These values were altered by the modules edit form.');
    }

    public function testBackslashValuesRenderWithoutExecuting(): void
    {
        $this->loginToAdmin();
        $db = $this->bootstrapLegacyDbConnection();

        $payload = $this->interpolationPayload($this->seedCanary($db));
        $values = self::BACKSLASH_VALUES + [
            'escaped quote breakout' => "\\'." . substr($payload, 1, -1) . ".'",
            'escaped interpolation' => '\\' . $payload,
        ];

        foreach ($values as $label => $value) {
            $this->seedModuleValue($db, $value);

            $page = $this->visitAdminCommand(self::EDIT_COMMAND);

            $this->assertCanaryUntouched($db, "The '$label' value was executed by eval().");
            $page->assertOk();
            $this->assertSame(
                trim(stripslashes($value)),
                $page->formDefaults('modules')[self::FIELD_NAME] ?? null,
                "The '$label' value was not rendered as objectInfo passed it."
            );
        }
    }

    /**
     * Posts the rendered edit form untouched. The in-process runner assigns data straight to
     * $_POST, so array-notation field names are re-expanded the way PHP's form decoding would.
     */
    private function submitModulesForm(FeatureResponse $page): FeatureResponse
    {
        $action = $page->formAction('modules');
        $this->assertNotNull($action, 'The modules edit form was not rendered.');

        parse_str(http_build_query($page->formDefaults('modules')), $data);
        $this->assertIsArray($data['configuration'] ?? null, 'Expected posted configuration values to be an array.');

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
     * Complex-interpolation syntax that, inside a double-quoted eval()'d string, calls
     * $db->Execute() to flip the canary row. It ends in RecordCount() so an executed payload
     * yields a string-safe int and the page still renders, leaving the canary as the signal.
     */
    private function interpolationPayload(int $canaryId): string
    {
        return '{$db->Execute(\'UPDATE \' . TABLE_CONFIGURATION . \' SET configuration_value = 1 WHERE configuration_id = ' . $canaryId . '\')->RecordCount()}';
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

    private function seedModuleValue(\queryFactory $db, string $value): void
    {
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_value = '" . addslashes($value) . "',
                    set_function = 'zen_cfg_textarea(',
                    last_modified = '" . self::SENTINEL_DATE . "'
              WHERE configuration_key = '" . self::MODULE_KEY . "'
              LIMIT 1"
        );
        $this->assertSame($value, $this->moduleValue($db), 'Fixture value was not stored.');
    }

    private function moduleValue(\queryFactory $db): ?string
    {
        $result = $db->Execute(
            "SELECT configuration_value
               FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = '" . self::MODULE_KEY . "'
              LIMIT 1"
        );
        $this->assertFalse($result->EOF, 'Money Order is not installed in the test database.');

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
