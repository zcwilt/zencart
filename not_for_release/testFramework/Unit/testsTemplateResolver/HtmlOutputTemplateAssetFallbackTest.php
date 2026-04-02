<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Tests\Support\zcUnitTestCase;

class HtmlOutputTemplateAssetFallbackTest extends zcUnitTestCase
{
    private string $pluginRoot;
    private string $baseImage;
    private string $responsiveClassicDogfoodPluginImage;
    private string $templateDogfoodLanguageImage;

    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/TemplateResolver.php';
        require_once DIR_FS_CATALOG . 'includes/functions/html_output.php';

        $this->pluginRoot = DIR_FS_CATALOG . 'zc_plugins/UnitTestChildTheme/v1.0.0/';
        $this->baseImage = DIR_FS_CATALOG . 'includes/templates/responsive_classic/images/zz_unit_image.png';
        $this->responsiveClassicDogfoodPluginImage = DIR_FS_CATALOG . 'zc_plugins/ResponsiveClassic/v1.0.0/catalog/includes/templates/responsive_classic_dogfood/images/zz_unit_image.png';
        $this->templateDogfoodLanguageImage = DIR_FS_CATALOG . 'includes/languages/english/responsive_classic_dogfood/zz_unit_lang.png';

        @mkdir($this->pluginRoot . 'catalog/includes/templates/child_theme', 0777, true);
        @mkdir(dirname($this->baseImage), 0777, true);
        @mkdir(dirname($this->responsiveClassicDogfoodPluginImage), 0777, true);
        @mkdir(dirname($this->templateDogfoodLanguageImage), 0777, true);

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
        'baseTemplate' => 'responsive_classic_dogfood',
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
        file_put_contents($this->responsiveClassicDogfoodPluginImage, 'base-plugin');
        file_put_contents($this->templateDogfoodLanguageImage, 'lang');
    }

    public function tearDown(): void
    {
        @unlink($this->baseImage);
        @unlink($this->responsiveClassicDogfoodPluginImage);
        @unlink($this->templateDogfoodLanguageImage);
        $this->removeDirectory($this->pluginRoot);
        parent::tearDown();
    }

    public function testTemplateAssetFallbackUsesBaseTemplatePath(): void
    {
        $missingChildPath = 'zc_plugins/UnitTestChildTheme/v1.0.0/catalog/includes/templates/child_theme/images/zz_unit_image.png';

        $this->assertSame(
            'zc_plugins/ResponsiveClassic/v1.0.0/catalog/includes/templates/responsive_classic_dogfood/images/zz_unit_image.png',
            zen_resolve_template_fallback_asset_path($missingChildPath, 'child_theme')
        );
    }

    public function testTemplateLanguageAssetFallbackUsesBaseTemplatePath(): void
    {
        $missingChildLanguagePath = 'includes/languages/english/child_theme/zz_unit_lang.png';

        $this->assertSame(
            'includes/languages/english/responsive_classic_dogfood/zz_unit_lang.png',
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
