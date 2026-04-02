<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Tests\Support\zcUnitTestCase;

class FunctionsFilesTemplateResolutionTest extends zcUnitTestCase
{
    private string $pluginRoot;
    private string $moduleFixture;
    private string $sideboxFixture;
    private string $indexFilterFixture;
    private string $htmlIncludeFixture;

    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/TemplateResolver.php';
        require_once DIR_FS_CATALOG . 'includes/functions/functions_templates.php';
        require_once DIR_FS_CATALOG . 'includes/functions/functions_files.php';

        $this->pluginRoot = DIR_FS_CATALOG . 'zc_plugins/UnitTestChildTheme/v1.0.0/';
        $this->moduleFixture = $this->pluginRoot . 'catalog/includes/modules/child_theme/zz_unit_module.php';
        $this->sideboxFixture = $this->pluginRoot . 'catalog/includes/modules/sideboxes/child_theme/zz_unit_sidebox.php';
        $this->indexFilterFixture = $this->pluginRoot . 'catalog/includes/index_filters/child_theme/zz_unit_filter.php';
        $this->htmlIncludeFixture = DIR_FS_CATALOG . 'includes/languages/english/html_includes/responsive_classic/define_zz_unit.php';

        @mkdir(dirname($this->moduleFixture), 0777, true);
        @mkdir(dirname($this->sideboxFixture), 0777, true);
        @mkdir(dirname($this->indexFilterFixture), 0777, true);
        @mkdir(dirname($this->htmlIncludeFixture), 0777, true);

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
        'baseTemplate' => 'responsive_classic',
        'infoFile' => 'catalog/includes/templates/child_theme/template_info.php',
    ],
];
PHP
        );
        @mkdir($this->pluginRoot . 'catalog/includes/templates/child_theme', 0777, true);
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

        file_put_contents($this->moduleFixture, "<?php\n");
        file_put_contents($this->sideboxFixture, "<?php\n");
        file_put_contents($this->indexFilterFixture, "<?php\n");
        file_put_contents($this->htmlIncludeFixture, "<?php\n");

        $_SESSION['language'] = 'english';
        $GLOBALS['template_dir'] = 'child_theme';
    }

    public function tearDown(): void
    {
        @unlink($this->moduleFixture);
        @unlink($this->sideboxFixture);
        @unlink($this->indexFilterFixture);
        @unlink($this->htmlIncludeFixture);
        $this->removeDirectory($this->pluginRoot);
        parent::tearDown();
    }

    public function testGetModuleDirectoryReturnsPluginRelativePath(): void
    {
        $this->assertSame(
            '../../zc_plugins/UnitTestChildTheme/v1.0.0/catalog/includes/modules/child_theme/zz_unit_module.php',
            zen_get_module_directory('zz_unit_module.php')
        );
    }

    public function testGetModuleSideboxDirectoryReturnsPluginRelativePath(): void
    {
        $this->assertSame(
            '../../zc_plugins/UnitTestChildTheme/v1.0.0/catalog/includes/modules/sideboxes/child_theme/zz_unit_sidebox.php',
            zen_get_module_sidebox_directory('zz_unit_sidebox.php')
        );
    }

    public function testGetIndexFiltersDirectoryReturnsPluginCatalogPath(): void
    {
        $this->assertSame(
            'zc_plugins/UnitTestChildTheme/v1.0.0/catalog/includes/index_filters/child_theme/zz_unit_filter.php',
            zen_get_index_filters_directory('zz_unit_filter.php')
        );
    }

    public function testGetFileDirectoryFallsBackThroughInheritedTemplateDirectories(): void
    {
        $this->assertSame(
            DIR_WS_LANGUAGES . $_SESSION['language'] . '/html_includes/responsive_classic/define_zz_unit.php',
            zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/html_includes/', 'define_zz_unit.php')
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
