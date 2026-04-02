<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsTemplateResolver;

use Tests\Support\zcUnitTestCase;
use Zencart\FileSystem\FileSystem;
use Zencart\PageLoader\PageLoader;

class PageLoaderTemplateResolutionTest extends zcUnitTestCase
{
    private string $childThemePluginPath;
    private string $overlayPluginPath;
    private string $responsiveClassicDogfoodPluginCssFixture;

    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/FileSystem.php';
        require_once DIR_FS_CATALOG . 'includes/classes/TemplateResolver.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/Singleton.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ResourceLoaders/PageLoader.php';

        $this->childThemePluginPath = DIR_FS_CATALOG . 'zc_plugins/UnitTestChildTheme/v1.0.0/';
        $this->overlayPluginPath = DIR_FS_CATALOG . 'zc_plugins/UnitTestTemplateOverlay/v1.0.0/';
        $this->responsiveClassicDogfoodPluginCssFixture = DIR_FS_CATALOG . 'zc_plugins/ResponsiveClassic/v1.0.0/catalog/includes/templates/responsive_classic_dogfood/css/zz_test_base.css';

        mkdir($this->childThemePluginPath . 'catalog/includes/templates/child_theme/css', 0777, true);
        mkdir($this->childThemePluginPath . 'catalog/includes/templates/child_theme/common', 0777, true);
        mkdir($this->overlayPluginPath . 'catalog/includes/templates/responsive_classic_dogfood/common', 0777, true);
        mkdir($this->overlayPluginPath . 'catalog/includes/templates/default/css', 0777, true);

        file_put_contents(
            $this->childThemePluginPath . 'manifest.php',
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
            $this->childThemePluginPath . 'catalog/includes/templates/child_theme/template_info.php',
            <<<'PHP'
<?php
$template_name = 'Child Theme';
$template_version = '1.0.0';
$template_author = 'Zen Cart';
$template_description = 'Unit test child theme';
$template_screenshot = 'screenshot.png';
PHP
        );
        file_put_contents($this->childThemePluginPath . 'catalog/includes/templates/child_theme/css/zz_test_child.css', '/* child */');

        file_put_contents(
            $this->overlayPluginPath . 'manifest.php',
            <<<'PHP'
<?php
return [
    'pluginVersion' => 'v1.0.0',
    'pluginName' => 'Unit Test Overlay',
    'pluginCapabilities' => ['template-overlay'],
    'template' => [
        'type' => 'overlay',
        'targets' => ['responsive_classic_dogfood', 'default'],
    ],
];
PHP
        );
        file_put_contents(
            $this->overlayPluginPath . 'catalog/includes/templates/responsive_classic_dogfood/common/tpl_overlay_unit_test.php',
            "<?php\n"
        );
        file_put_contents(
            $this->overlayPluginPath . 'catalog/includes/templates/default/css/zz_test_overlay.css',
            '/* overlay */'
        );
        file_put_contents($this->responsiveClassicDogfoodPluginCssFixture, '/* base */');
    }

    public function tearDown(): void
    {
        @unlink($this->responsiveClassicDogfoodPluginCssFixture);
        $this->removeDirectory($this->childThemePluginPath);
        $this->removeDirectory($this->overlayPluginPath);
        parent::tearDown();
    }

    public function testGetTemplateDirectoryFallsBackToBaseTemplateForChildTheme(): void
    {
        $pageLoader = PageLoader::getInstance();
        $pageLoader->init($this->getInstalledPlugins(), 'index', new FileSystem());

        $directory = $pageLoader->getTemplateDirectory('html_header.php', 'child_theme', 'index', 'common');

        $this->assertSame('zc_plugins/ResponsiveClassic/v1.0.0/catalog/includes/templates/responsive_classic_dogfood/common', $directory);
    }

    public function testGetTemplateDirectoryFindsNamedOverlayBeforeDefaultFallback(): void
    {
        $pageLoader = PageLoader::getInstance();
        $pageLoader->init($this->getInstalledPlugins(), 'index', new FileSystem());

        $directory = $pageLoader->getTemplateDirectory('tpl_overlay_unit_test.php', DIR_WS_TEMPLATES . 'responsive_classic_dogfood/', 'index', 'common');

        $this->assertSame('zc_plugins/UnitTestTemplateOverlay/v1.0.0/catalog/includes/templates/responsive_classic_dogfood/common', $directory);
    }

    public function testGetTemplatePartMergesChildBaseAndOverlayAssets(): void
    {
        $pageLoader = PageLoader::getInstance();
        $pageLoader->init($this->getInstalledPlugins(), 'index', new FileSystem());

        $files = $pageLoader->getTemplatePart('includes/templates/child_theme/css', '/^zz_test_/', '.css');

        $this->assertSame(
            ['zz_test_base.css', 'zz_test_child.css', 'zz_test_overlay.css'],
            $files
        );
    }

    private function getInstalledPlugins(): array
    {
        return [
            ['unique_key' => 'UnitTestChildTheme', 'version' => 'v1.0.0'],
            ['unique_key' => 'UnitTestTemplateOverlay', 'version' => 'v1.0.0'],
        ];
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
