<?php
/**
 *
 * @package classes
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 */

namespace Zencart\LanguageLoader;

use Zencart\FileSystem;

class LanguageLoader
{

    public function __construct($pluginList, $currentPage)
    {
//        $this->pluginList = $pluginList;
        $this->pluginList = []; // @todo temp
        $this->currentPage = $currentPage;
    }

    public function loadLanguageDefines()
    {
        $this->loadLanguageForView();
        $this->loadLanguageExtraDefinitions();
        $this->loadBaseLanguageFile();
    }


    protected function loadLanguageForView()
    {
        if (is_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->currentPage)) {
            include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->currentPage);
        }
        foreach ($this->pluginList as $plugin) {
            $pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'];
            $langFile = $pluginDir . '/admin/includes/languages/'  . $_SESSION['language'] . '/' . $this->currentPage;
            if (is_file($langFile)) {
                include_once($langFile);
            }
        }
    }

    protected function loadLanguageExtraDefinitions()
    {
        $fs = FileSystem::getInstance();
        $fs->loadFilesFromDirectory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra_definitions', '~^[^\._].*\.php$~i');
        foreach ($this->pluginList as $plugin) {
            $pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'];
            $extrasDir = $pluginDir . '/admin/includes/languages/' . $_SESSION['language'] . '/extra_definitions';
            $this->loadFilesFromDirectory($extrasDir, '~^[^\._].*\.php$~i');
        }

    }

    protected function loadBaseLanguageFile()
    {
        require_once(DIR_WS_LANGUAGES . $_SESSION['language'] . '.php');
    }

}