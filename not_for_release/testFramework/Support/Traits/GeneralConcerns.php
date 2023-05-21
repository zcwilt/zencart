<?php

namespace Tests\Support\Traits;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

trait GeneralConcerns
{
    public function detectUser()
    {
        $user = $_SERVER['USER'] ?? $_SERVER['MY_USER'];
        return $user;
    }

    public function loadConfigureFile($context)
    {
        $user = $this->detectUser();
        $basePath = $configFile = TESTCWD . 'Support/configs/';
        $configFile =  $basePath . $user . '.' . $context . '.configure.php';
        if (!file_exists($configFile)) {
            die('could not find config file ' .$configFile);
        }
        require($configFile);
    }


    public function loadMigrationAndSeeders()
    {
        $this->databaseSetup(); //setup Capsule
        $this->runMigrations();
        $this->runInitialSeeders();
    }

    public function createHttpBrowser()
    {
        $this->browser = new HttpBrowser(HttpClient::create());
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

}
