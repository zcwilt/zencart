<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsConfigField;

use Tests\Support\zcUnitTestCase;
use Zencart\PluginSupport\ScriptedInstallHelpers;

/**
 * Covers ScriptedInstallHelpers::configFieldRenderer(), the compatibility-layer
 * convenience for plugin installers building a 'renderer' property value.
 * addConfigurationKey()/updateConfigurationKey() themselves need a live DB
 * connection (queryFactory) and are exercised by FeatureAdmin tests, not here.
 */
class ScriptedInstallHelpersRendererTest extends zcUnitTestCase
{
    private function subject(): object
    {
        return new class {
            use ScriptedInstallHelpers;

            public function callConfigFieldRenderer(string $renderer, array $params = [], ?string $formatter = null): string
            {
                return $this->configFieldRenderer($renderer, $params, $formatter);
            }
        };
    }

    public function testBuildsRendererOnlyPayload(): void
    {
        $json = $this->subject()->callConfigFieldRenderer('zen_cfg_select_option', ['options' => ['true', 'false']]);

        $this->assertSame(
            ['renderer' => 'zen_cfg_select_option', 'params' => ['options' => ['true', 'false']]],
            json_decode($json, true)
        );
    }

    public function testDefaultsToEmptyParams(): void
    {
        $json = $this->subject()->callConfigFieldRenderer('zen_cfg_read_only');

        $this->assertSame(
            ['renderer' => 'zen_cfg_read_only', 'params' => []],
            json_decode($json, true)
        );
    }

    public function testIncludesFormatterWhenProvided(): void
    {
        $json = $this->subject()->callConfigFieldRenderer(
            'zen_cfg_pull_down_order_statuses',
            [],
            'zen_get_order_status_name'
        );

        $this->assertSame(
            [
                'renderer' => 'zen_cfg_pull_down_order_statuses',
                'params' => [],
                'formatter' => 'zen_get_order_status_name',
            ],
            json_decode($json, true)
        );
    }

    public function testOmitsFormatterKeyWhenNotProvided(): void
    {
        $json = $this->subject()->callConfigFieldRenderer('zen_cfg_textarea');

        $this->assertArrayNotHasKey('formatter', json_decode($json, true));
    }

    public function testOutputIsValidJsonConsumableByConfigFieldRowResolver(): void
    {
        $json = $this->subject()->callConfigFieldRenderer('zen_cfg_select_option', ['options' => ['a', 'b']]);

        $registry = new \Zencart\ConfigField\ConfigFieldRegistry();
        $registry->registerRenderer('zen_cfg_select_option', new class implements \Zencart\ConfigField\ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return implode(',', $params['options'] ?? []);
            }
        });
        $resolver = new \Zencart\ConfigField\ConfigFieldRowResolver($registry);

        $this->assertSame('a,b', $resolver->renderField($json, 'a', 'cfg_1'));
    }
}
