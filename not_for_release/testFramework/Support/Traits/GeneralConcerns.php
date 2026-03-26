<?php

namespace Tests\Support\Traits;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Tests\Support\TestConfigResolver;
use Tests\Support\TestFrameworkFilesystem;


trait GeneralConcerns
{
    protected HttpBrowser $browser;
    private ?TestFrameworkFilesystem $testFrameworkFilesystem = null;

    public static function detectUser()
    {
        return TestConfigResolver::detectUser();
    }

    public static function loadConfigureFile($context)
    {
        if ($context !== 'main' && defined('HTTP_SERVER') && defined('DB_TYPE')) {
            return;
        }

        return TestConfigResolver::loadConfig($context, TESTCWD . 'Support/configs/');
    }


    public static function loadMigrationAndSeeders($mainConfigs = [])
    {
        self::databaseSetup(); //setup Capsule
        self::runDatabaseLoader($mainConfigs);
    }

    public function createHttpBrowser(): void
    {
        $this->browser = new HttpBrowser(HttpClient::create());
    }

    public static function locateElementInPageSource(string $element_lookup_text, string $page_source, int $length = 1500): string
    {
        $position = strpos($page_source, $element_lookup_text);
        // if not found, return whole $page_source; but if found, only return a portion of the page
        return ($position === false) ? $page_source : substr($page_source, $position, $length);
    }

    /**
     * @param $page
     * @return mixed
     * @todo refactor - use zen_href_link
     */
    protected function buildStoreLink($page)
    {
        $URI = HTTP_SERVER . '/index.php?main_page='.$page;
        return $URI;
    }
    protected function buildAdminLink($page)
    {
        $URI = HTTP_SERVER . '/admin/index.php?cmd='.$page;
        return $URI;
    }


    protected function browserAdminLogin()
    {
        $this->runCustomSeeder('StoreWizardSeeder');
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Admin Login', (string)$response->getContent() );
        $this->browser->submitForm('Submit', [
            'admin_name' => 'Admin',
            'admin_pass' => 'password',
        ]);
    }

    // PLUGIN STUFF

    protected function installPluginToFilesystem(string $pluginName, string $version): void
    {
        $this->filesystemHelper()->installPlugin($pluginName, DIR_FS_CATALOG, ROOTCWD);
    }

    protected function removePlugin(string $pluginName, string $version): void
    {
        $this->filesystemHelper()->removePlugin($pluginName, $version, DIR_FS_CATALOG);
    }

    protected function filesystemHelper(): TestFrameworkFilesystem
    {
        if (!$this->testFrameworkFilesystem instanceof TestFrameworkFilesystem) {
            $this->testFrameworkFilesystem = new TestFrameworkFilesystem();
        }

        return $this->testFrameworkFilesystem;
    }
}
