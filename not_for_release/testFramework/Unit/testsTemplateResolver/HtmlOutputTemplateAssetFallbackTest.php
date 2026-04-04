<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Tests\Support\zcUnitTestCase;

class HtmlOutputTemplateAssetFallbackTest extends zcUnitTestCase
{
    private string $baseThemePluginRoot;
    private string $pluginRoot;
    private string $baseImage;
    private string $baseThemePluginImage;
    private string $templateDogfoodLanguageImage;

    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/TemplateResolver.php';
        require_once DIR_FS_CATALOG . 'includes/functions/html_output.php';

        $this->baseThemePluginRoot = DIR_FS_CATALOG . 'zc_plugins/UnitTestBaseTheme/v1.0.0/';
        $this->pluginRoot = DIR_FS_CATALOG . 'zc_plugins/UnitTestChildTheme/v1.0.0/';
        $this->baseImage = DIR_FS_CATALOG . 'includes/templates/responsive_classic/images/zz_unit_image.png';
        $this->baseThemePluginImage = $this->baseThemePluginRoot . 'catalog/includes/templates/unit_test_base_theme/images/zz_unit_image.png';
        $this->templateDogfoodLanguageImage = DIR_FS_CATALOG . 'includes/languages/english/unit_test_base_theme/zz_unit_lang.png';

        @mkdir($this->baseThemePluginRoot . 'catalog/includes/templates/unit_test_base_theme/images', 0777, true);
        @mkdir($this->pluginRoot . 'catalog/includes/templates/child_theme', 0777, true);
        @mkdir(dirname($this->baseImage), 0777, true);
        @mkdir(dirname($this->templateDogfoodLanguageImage), 0777, true);

        file_put_contents(
            $this->baseThemePluginRoot . 'manifest.php',
            <<<'PHP'
<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Unit Test Base Theme',
    'pluginCapabilities' => ['template'],
    'template' => [
        'key' => 'unit_test_base_theme',
        'type' => 'selectable',
        'baseTemplate' => 'template_default',
        'infoFile' => 'catalog/includes/templates/unit_test_base_theme/template_info.php',
    ],
];
PHP
        );
        file_put_contents(
            $this->baseThemePluginRoot . 'catalog/includes/templates/unit_test_base_theme/template_info.php',
            <<<'PHP'
<?php
$template_name = 'Unit Test Base Theme';
$template_version = '1.0.0';
$template_author = 'Zen Cart';
$template_description = 'Unit test plugin-backed base theme';
$template_screenshot = 'screenshot.png';
PHP
        );
        file_put_contents(
            $this->pluginRoot . 'manifest.php',
            <<<'PHP'
<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Unit Test Child Theme',
    'pluginCapabilities' => ['template'],
    'template' => [
        'key' => 'child_theme',
        'type' => 'selectable',
        'baseTemplate' => 'unit_test_base_theme',
        'infoFile' => 'catalog/includes/templates/child_theme/template_info.php',
    ],
];
PHP
        );
        file_put_contents(
            $this->pluginRoot . 'catalog/includes/templates/child_theme/template_info.php',
            <<<'PHP'
<?php
$template_name = 'Child Theme';
$template_version = '1.0.0';
$template_author = 'Zen Cart';
$template_description = 'Unit test child theme';
$template_screenshot = 'screenshot.png';
PHP
        );

        file_put_contents($this->baseImage, 'base');
        file_put_contents($this->baseThemePluginImage, 'base-plugin');
        file_put_contents($this->templateDogfoodLanguageImage, 'lang');
    }

    public function tearDown(): void
    {
        @unlink($this->baseImage);
        @unlink($this->templateDogfoodLanguageImage);
        $this->removeDirectory($this->baseThemePluginRoot);
        $this->removeDirectory($this->pluginRoot);
        parent::tearDown();
    }

    public function testTemplateAssetFallbackUsesBaseTemplatePath(): void
    {
        $missingChildPath = 'zc_plugins/UnitTestChildTheme/v1.0.0/catalog/includes/templates/child_theme/images/zz_unit_image.png';

        $this->assertSame(
            'zc_plugins/UnitTestBaseTheme/v1.0.0/catalog/includes/templates/unit_test_base_theme/images/zz_unit_image.png',
            zen_resolve_template_fallback_asset_path($missingChildPath, 'child_theme')
        );
    }

    public function testTemplateLanguageAssetFallbackUsesBaseTemplatePath(): void
    {
        $missingChildLanguagePath = 'includes/languages/english/child_theme/zz_unit_lang.png';

        $this->assertSame(
            'includes/languages/english/unit_test_base_theme/zz_unit_lang.png',
            zen_resolve_template_fallback_asset_path($missingChildLanguagePath, 'child_theme')
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
