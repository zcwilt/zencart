<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsTemplateResolver;

use Tests\Support\zcUnitTestCase;
use Zencart\ResourceLoaders\TemplateResolver;

class TemplateResolverTest extends zcUnitTestCase
{
    private string $fixtureRoot;

    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/ResourceLoaders/TemplateResolver.php';
        $this->fixtureRoot = sys_get_temp_dir() . '/zencart-template-resolver-' . uniqid('', true);
        mkdir($this->fixtureRoot . '/includes/templates/template_default', 0777, true);
        mkdir($this->fixtureRoot . '/includes/templates/responsive_classic', 0777, true);
        mkdir($this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme', 0777, true);

        $this->writeTemplateInfo(
            $this->fixtureRoot . '/includes/templates/template_default/template_info.php',
            'Template Default'
        );
        $this->writeTemplateInfo(
            $this->fixtureRoot . '/includes/templates/responsive_classic/template_info.php',
            'Responsive Classic'
        );
        file_put_contents($this->fixtureRoot . '/includes/templates/responsive_classic/template_settings.php', "<?php\n");
        $this->writeTemplateInfo(
            $this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme/template_info.php',
            'Child Theme'
        );
        file_put_contents($this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme/template_settings.php', "<?php\n");
        file_put_contents(
            $this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/manifest.php',
            <<<'PHP'
<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Child Theme',
    'pluginCapabilities' => ['template'],
    'template' => [
        'key' => 'child_theme',
        'type' => 'selectable',
        'baseTemplate' => 'responsive_classic',
        'infoFile' => 'catalog/includes/templates/child_theme/template_info.php',
        'settingsFile' => 'catalog/includes/templates/child_theme/template_settings.php',
    ],
];
PHP
        );
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->fixtureRoot);
        parent::tearDown();
    }

    public function testSelectableTemplatesMergeCoreAndPluginTemplates(): void
    {
        $resolver = new TemplateResolver(
            $this->fixtureRoot,
            $this->fixtureRoot . '/includes/templates',
            $this->fixtureRoot . '/zc_plugins'
        );

        $templates = $resolver->getSelectableTemplates();

        $this->assertArrayHasKey('responsive_classic', $templates);
        $this->assertArrayHasKey('child_theme', $templates);
        $this->assertArrayNotHasKey('template_default', $templates);
        $this->assertSame('Responsive Classic', $templates['responsive_classic']['name']);
        $this->assertSame('Child Theme', $templates['child_theme']['name']);
        $this->assertTrue($templates['child_theme']['has_template_settings']);
    }

    public function testPluginTemplateRecordExposesBaseTemplateAndPaths(): void
    {
        $resolver = new TemplateResolver(
            $this->fixtureRoot,
            $this->fixtureRoot . '/includes/templates',
            $this->fixtureRoot . '/zc_plugins'
        );

        $record = $resolver->getTemplateRecord('child_theme');

        $this->assertNotNull($record);
        $this->assertTrue($resolver->isPluginTemplate('child_theme'));
        $this->assertSame('responsive_classic', $resolver->getBaseTemplate('child_theme'));
        $this->assertSame(
            $this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme/',
            $resolver->getTemplateFilesystemPath('child_theme')
        );
        $this->assertSame(
            'zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme/',
            $resolver->getTemplateCatalogPath('child_theme')
        );
        $this->assertSame(
            '/zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme/',
            $resolver->getTemplateWebPath('child_theme')
        );
        $this->assertSame(
            ['child_theme', 'responsive_classic', 'template_default'],
            $resolver->getTemplateInheritanceChain('child_theme')
        );
    }

    public function testPluginTemplateCanOverrideCoreTemplateRecordByKey(): void
    {
        file_put_contents(
            $this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/manifest.php',
            <<<'PHP'
<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Responsive Classic Plugin',
    'pluginCapabilities' => ['template'],
    'template' => [
        'key' => 'responsive_classic',
        'type' => 'selectable',
        'baseTemplate' => 'template_default',
        'infoFile' => 'catalog/includes/templates/child_theme/template_info.php',
    ],
];
PHP
        );

        $resolver = new TemplateResolver(
            $this->fixtureRoot,
            $this->fixtureRoot . '/includes/templates',
            $this->fixtureRoot . '/zc_plugins'
        );

        $record = $resolver->getTemplateRecord('responsive_classic');

        $this->assertNotNull($record);
        $this->assertTrue($record['is_plugin_template']);
        $this->assertSame('Child Theme', $record['name']);
        $this->assertSame('zc_plugins/ChildTheme/v1.0.0/catalog/includes/templates/child_theme/', $record['template_catalog_path']);
    }

    public function testPluginTemplateRecordUsesCustomSettingsFilePath(): void
    {
        mkdir($this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/config', 0777, true);
        file_put_contents($this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/config/child-settings.php', "<?php\n");
        file_put_contents(
            $this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/manifest.php',
            <<<'PHP'
<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Child Theme',
    'pluginCapabilities' => ['template'],
    'template' => [
        'key' => 'child_theme',
        'type' => 'selectable',
        'baseTemplate' => 'responsive_classic',
        'infoFile' => 'catalog/includes/templates/child_theme/template_info.php',
        'settingsFile' => 'catalog/config/child-settings.php',
    ],
];
PHP
        );

        $resolver = new TemplateResolver(
            $this->fixtureRoot,
            $this->fixtureRoot . '/includes/templates',
            $this->fixtureRoot . '/zc_plugins'
        );

        $record = $resolver->getTemplateRecord('child_theme');

        $this->assertNotNull($record);
        $this->assertTrue($record['has_template_settings']);
        $this->assertSame(
            $this->fixtureRoot . '/zc_plugins/ChildTheme/v1.0.0/catalog/config/child-settings.php',
            $record['template_settings_path']
        );
    }

    private function writeTemplateInfo(string $path, string $templateName): void
    {
        file_put_contents(
            $path,
            <<<PHP
<?php
\$template_name = '{$templateName}';
\$template_version = '1.0.0';
\$template_author = 'Zen Cart';
\$template_description = '{$templateName} description';
\$template_screenshot = 'screenshot.png';
PHP
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
