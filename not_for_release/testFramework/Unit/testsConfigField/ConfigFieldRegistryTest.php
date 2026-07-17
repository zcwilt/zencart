<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsConfigField;

use Tests\Support\zcUnitTestCase;
use Zencart\ConfigField\ConfigFieldFormatterInterface;
use Zencart\ConfigField\ConfigFieldRegistry;
use Zencart\ConfigField\ConfigFieldRendererInterface;

class ConfigFieldRegistryTest extends zcUnitTestCase
{
    public function testUnknownRendererKeyResolvesToNull(): void
    {
        $registry = new ConfigFieldRegistry();
        $this->assertFalse($registry->hasRenderer('nope'));
        $this->assertNull($registry->resolveRenderer('nope'));
        $this->assertNull($registry->render('nope', 'value', 'field'));
    }

    public function testUnknownFormatterKeyResolvesToNull(): void
    {
        $registry = new ConfigFieldRegistry();
        $this->assertFalse($registry->hasFormatter('nope'));
        $this->assertNull($registry->resolveFormatter('nope'));
        $this->assertNull($registry->format('nope', 'value'));
    }

    public function testRegisterAndResolveRenderer(): void
    {
        $registry = new ConfigFieldRegistry();
        $renderer = new class implements ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return "rendered:$value:$fieldName:" . ($params['suffix'] ?? '');
            }
        };

        $registry->registerRenderer('my_key', $renderer);

        $this->assertTrue($registry->hasRenderer('my_key'));
        $this->assertSame($renderer, $registry->resolveRenderer('my_key'));
        $this->assertSame('rendered:v:f:x', $registry->render('my_key', 'v', 'f', ['suffix' => 'x']));
    }

    public function testRegisterAndResolveFormatter(): void
    {
        $registry = new ConfigFieldRegistry();
        $formatter = new class implements ConfigFieldFormatterInterface {
            public function format(string $value, array $params = []): string
            {
                return "formatted:$value";
            }
        };

        $registry->registerFormatter('my_key', $formatter);

        $this->assertTrue($registry->hasFormatter('my_key'));
        $this->assertSame($formatter, $registry->resolveFormatter('my_key'));
        $this->assertSame('formatted:v', $registry->format('my_key', 'v'));
    }

    public function testReRegisteringAKeyOverridesThePreviousEntry(): void
    {
        $registry = new ConfigFieldRegistry();
        $first = new class implements ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return 'first';
            }
        };
        $second = new class implements ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return 'second';
            }
        };

        $registry->registerRenderer('my_key', $first);
        $registry->registerRenderer('my_key', $second);

        $this->assertSame('second', $registry->render('my_key', 'v', 'f'));
    }

    public function testRendererAndFormatterKeySpacesAreIndependent(): void
    {
        // The same string can be registered as both a renderer key and a formatter
        // key without colliding (mirrors legacy set_function/use_function coexisting
        // on the same configuration row under different column names).
        $registry = new ConfigFieldRegistry();
        $renderer = new class implements ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return 'as-renderer';
            }
        };
        $formatter = new class implements ConfigFieldFormatterInterface {
            public function format(string $value, array $params = []): string
            {
                return 'as-formatter';
            }
        };

        $registry->registerRenderer('shared_key', $renderer);
        $registry->registerFormatter('shared_key', $formatter);

        $this->assertSame('as-renderer', $registry->render('shared_key', 'v', 'f'));
        $this->assertSame('as-formatter', $registry->format('shared_key', 'v'));
    }

    public function testBootstrapCoreRegistersAllCoreRenderersAndFormatters(): void
    {
        $registry = new ConfigFieldRegistry();
        $registry->bootstrapCore();

        $expectedRenderers = [
            'zen_cfg_select_coupon_id',
            'zen_cfg_pull_down_country_list',
            'zen_cfg_pull_down_country_list_none',
            'zen_cfg_pull_down_zone_list',
            'zen_cfg_pull_down_tax_classes',
            'zen_cfg_textarea',
            'zen_cfg_textarea_small',
            'zen_cfg_pull_down_htmleditors',
            'zen_cfg_pull_down_exchange_rate_sources',
            'zen_cfg_password_input',
            'zen_cfg_select_option',
            'zen_cfg_select_drop_down',
            'zen_cfg_pull_down_zone_classes',
            'zen_cfg_pull_down_order_statuses',
            'zen_cfg_select_multioption',
            'zen_cfg_select_multioption_pairs',
            'zen_cfg_read_only',
        ];
        foreach ($expectedRenderers as $key) {
            $this->assertTrue($registry->hasRenderer($key), "Expected core renderer key '$key' to be registered");
        }

        $expectedFormatters = [
            'zen_get_country_name',
            'zen_cfg_get_zone_name',
            'zen_get_zone_class_title',
            'zen_get_order_status_name',
            'zen_get_tax_class_title',
            'zen_get_configuration_group_value',
            'zen_cfg_password_display',
            'currencies->format',
        ];
        foreach ($expectedFormatters as $key) {
            $this->assertTrue($registry->hasFormatter($key), "Expected core formatter key '$key' to be registered");
        }
    }

    public function testRegisterRendererClassInstantiatesLazilyAndMemoizes(): void
    {
        FixtureCountingRenderer::$constructedCount = 0;
        $registry = new ConfigFieldRegistry();

        $registry->registerRendererClass('lazy_key', FixtureCountingRenderer::class);
        $this->assertSame(0, FixtureCountingRenderer::$constructedCount, 'registering a class string must not instantiate it');
        $this->assertTrue($registry->hasRenderer('lazy_key'));

        $first = $registry->resolveRenderer('lazy_key');
        $this->assertSame(1, FixtureCountingRenderer::$constructedCount);

        $second = $registry->resolveRenderer('lazy_key');
        $this->assertSame(1, FixtureCountingRenderer::$constructedCount, 'a second resolve must reuse the memoized instance, not construct again');
        $this->assertSame($first, $second);

        $this->assertSame('counted', $registry->render('lazy_key', 'v', 'f'));
    }

    public function testRegisterFormatterClassInstantiatesLazilyAndMemoizes(): void
    {
        FixtureCountingFormatter::$constructedCount = 0;
        $registry = new ConfigFieldRegistry();

        $registry->registerFormatterClass('lazy_key', FixtureCountingFormatter::class);
        $this->assertSame(0, FixtureCountingFormatter::$constructedCount);

        $registry->resolveFormatter('lazy_key');
        $registry->resolveFormatter('lazy_key');
        $this->assertSame(1, FixtureCountingFormatter::$constructedCount);
    }

    public function testRegisterRendererClassRejectsAClassThatDoesNotImplementTheInterface(): void
    {
        $registry = new ConfigFieldRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $registry->registerRendererClass('bad_key', FixtureNotARenderer::class);
    }

    public function testRegisterFormatterClassRejectsAClassThatDoesNotImplementTheInterface(): void
    {
        $registry = new ConfigFieldRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $registry->registerFormatterClass('bad_key', FixtureNotARenderer::class);
    }
}

class FixtureCountingRenderer implements ConfigFieldRendererInterface
{
    public static int $constructedCount = 0;

    public function __construct()
    {
        self::$constructedCount++;
    }

    public function render(string $value, string $fieldName, array $params = []): string
    {
        return 'counted';
    }
}

class FixtureCountingFormatter implements ConfigFieldFormatterInterface
{
    public static int $constructedCount = 0;

    public function __construct()
    {
        self::$constructedCount++;
    }

    public function format(string $value, array $params = []): string
    {
        return 'counted';
    }
}

class FixtureNotARenderer
{
}
