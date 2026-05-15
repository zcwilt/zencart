<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsTemplateResolver;

use Tests\Support\zcUnitTestCase;

class AdminInitTemplatesTest extends zcUnitTestCase
{
    protected $runTestInSeparateProcess = true;
    protected $preserveGlobalState = false;

    private string $fixtureRoot;
    private string $repoRoot;

    public function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = realpath(__DIR__ . '/../../../../') . '/';

        require_once $this->repoRoot . 'includes/functions/zen_define_default.php';
        require_once $this->repoRoot . 'includes/classes/class.base.php';
        require_once $this->repoRoot . 'includes/classes/db/mysql/query_factory.php';
        require_once $this->repoRoot . 'includes/classes/TemplateDto.php';
        require_once $this->repoRoot . 'includes/classes/TemplateSelect.php';
        require_once $this->repoRoot . 'includes/classes/ResourceLoaders/TemplateResolver.php';

        $this->fixtureRoot = sys_get_temp_dir() . '/zencart-admin-init-templates-' . uniqid('', true);
        mkdir($this->fixtureRoot . '/includes/templates/template_default', 0777, true);
        mkdir($this->fixtureRoot . '/includes/templates/admin_template_test', 0777, true);
        mkdir($this->fixtureRoot . '/includes/classes', 0777, true);

        $this->writeTemplateInfo(
            $this->fixtureRoot . '/includes/templates/template_default/template_info.php',
            'Template Default'
        );
        $this->writeTemplateInfo(
            $this->fixtureRoot . '/includes/templates/admin_template_test/template_info.php',
            'Admin Template Test'
        );

        file_put_contents(
            $this->fixtureRoot . '/includes/classes/template_func.php',
            <<<'PHP'
<?php
class template_func
{
    public function __construct(public string $directory)
    {
    }
}
PHP
        );
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->fixtureRoot);
        parent::tearDown();
    }

    public function testAdminInitTemplatesLoadsActiveTemplateWhenTemplateDirStartsEmpty(): void
    {
        define('IS_ADMIN_FLAG', true);
        define('DIR_FS_CATALOG', $this->fixtureRoot . '/');
        define('DIR_WS_CLASSES', 'includes/classes/');
        define('TABLE_TEMPLATE_SELECT', 'template_select');
        define('CHARSET', 'utf-8');
        define('HEADER_TITLE_TOP', 'Admin Home');
        define('TEXT_ADMIN_TAB_PREFIX', 'Admin');
        define('STORE_NAME', 'Zen Cart');

        $_SESSION['languages_id'] = 0;
        $_GET = [];

        $db = $this->getMockBuilder(\queryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $db->method('Execute')
            ->willReturnCallback(function (string $sql): \queryFactoryResult {
                if (stripos($sql, 'FROM ' . TABLE_TEMPLATE_SELECT) !== false) {
                    return $this->makeQueryResult([[
                        'template_id' => 1,
                        'template_dir' => 'admin_template_test',
                        'template_language' => 0,
                        'template_settings' => null,
                    ]]);
                }

                if (stripos($sql, 'plugin_control') !== false) {
                    return $this->makeQueryResult([]);
                }

                return $this->makeQueryResult([]);
            });
        $GLOBALS['db'] = $db;

        $PHP_SELF = 'index.php';
        $template_dir = '';

        include $this->repoRoot . 'admin/includes/init_includes/init_templates.php';

        $this->assertSame(
            'admin_template_test',
            $template_dir,
            'Expected admin init_templates to resolve the active template when $template_dir starts empty.'
        );
        $this->assertSame('includes/templates/admin_template_test/', DIR_WS_TEMPLATE);
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

    private function makeQueryResult(array $rows): \queryFactoryResult
    {
        $result = new \queryFactoryResult(null);
        $result->result = $rows;
        $result->is_cached = true;
        $result->cursor = 0;
        $result->fields = $rows[0] ?? [];
        $result->EOF = ($rows === []);

        return $result;
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
