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
use Zencart\ConfigField\ConfigFieldRowResolver;
use Zencart\ConfigField\SensitiveConfigFieldInterface;

class ConfigFieldRowResolverTest extends zcUnitTestCase
{
    private function registryWithEchoRenderer(string $key = 'zen_cfg_select_option'): ConfigFieldRegistry
    {
        $registry = new ConfigFieldRegistry();
        $registry->registerRenderer($key, new class implements ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return '<select name="' . $fieldName . '">' . $value . '</select>';
            }
        });
        $registry->registerFormatter($key, new class implements ConfigFieldFormatterInterface {
            public function format(string $value, array $params = []): string
            {
                return 'formatted-' . $value;
            }
        });
        return $registry;
    }

    public function testNullRendererColumnFallsBackToNull(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $this->assertNull($resolver->renderField(null, 'true', 'cfg_1'));
        $this->assertNull($resolver->renderField('', 'true', 'cfg_1'));
    }

    public function testValidRendererJsonResolvesThroughTheRegistry(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $json = '{"renderer":"zen_cfg_select_option","params":{"options":["true","false"]}}';

        $result = $resolver->renderField($json, 'true', 'cfg_1');

        $this->assertSame('<select name="cfg_1">true</select>', $result);
    }

    public function testValidFormatterJsonResolvesThroughTheRegistry(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $json = '{"formatter":"zen_cfg_select_option","params":{}}';

        $result = $resolver->formatField($json, 'true');

        $this->assertSame('formatted-true', $result);
    }

    public function testMalformedJsonFallsBackToNull(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $this->assertNull($resolver->renderField('{not valid json', 'true', 'cfg_1'));
        $this->assertNull($resolver->renderField('"just a string"', 'true', 'cfg_1'));
    }

    public function testMissingRendererKeyInJsonFallsBackToNull(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $this->assertNull($resolver->renderField('{"params":{}}', 'true', 'cfg_1'));
    }

    public function testUnregisteredRendererKeyFallsBackToNull(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $json = '{"renderer":"totally_unregistered_key"}';

        $this->assertNull($resolver->renderField($json, 'true', 'cfg_1'));
    }

    public function testThrowingRendererIsCaughtAndFallsBackToNull(): void
    {
        $registry = new ConfigFieldRegistry();
        $registry->registerRenderer('boom', new class implements ConfigFieldRendererInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                throw new \RuntimeException('adapter blew up');
            }
        });
        $resolver = new ConfigFieldRowResolver($registry);

        $result = $resolver->renderField('{"renderer":"boom"}', 'true', 'cfg_1');

        $this->assertNull($result, 'A throwing renderer must degrade to null (caller falls back to legacy path), not fatal');
    }

    public function testIsSensitiveReturnsFalseForNullOrEmptyOrMalformedJson(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $this->assertFalse($resolver->isSensitive(null));
        $this->assertFalse($resolver->isSensitive(''));
        $this->assertFalse($resolver->isSensitive('{not valid json'));
    }

    public function testIsSensitiveReturnsFalseWhenNeitherRendererNorFormatterIsSensitive(): void
    {
        $resolver = new ConfigFieldRowResolver($this->registryWithEchoRenderer());
        $json = '{"renderer":"zen_cfg_select_option","formatter":"zen_cfg_select_option"}';
        $this->assertFalse($resolver->isSensitive($json));
    }

    public function testIsSensitiveReturnsTrueWhenRendererImplementsTheMarkerInterface(): void
    {
        $registry = new ConfigFieldRegistry();
        $registry->registerRenderer('secret_field', new class implements ConfigFieldRendererInterface, SensitiveConfigFieldInterface {
            public function render(string $value, string $fieldName, array $params = []): string
            {
                return '';
            }
            public function isSensitive(): bool
            {
                return true;
            }
        });
        $resolver = new ConfigFieldRowResolver($registry);

        $this->assertTrue($resolver->isSensitive('{"renderer":"secret_field"}'));
    }

    public function testIsSensitiveReturnsTrueWhenFormatterImplementsTheMarkerInterface(): void
    {
        $registry = new ConfigFieldRegistry();
        $registry->registerFormatter('secret_field', new class implements ConfigFieldFormatterInterface, SensitiveConfigFieldInterface {
            public function format(string $value, array $params = []): string
            {
                return '';
            }
            public function isSensitive(): bool
            {
                return true;
            }
        });
        $resolver = new ConfigFieldRowResolver($registry);

        $this->assertTrue($resolver->isSensitive('{"formatter":"secret_field"}'));
    }

    public function testIsSensitiveReturnsFalseForUnregisteredKeys(): void
    {
        $resolver = new ConfigFieldRowResolver(new ConfigFieldRegistry());
        $this->assertFalse($resolver->isSensitive('{"renderer":"nope","formatter":"nope"}'));
    }
}
