<?php
/**
 *
 * @package classes
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 */

namespace Zencart\LanguageLoader;

use Zencart\FileSystem\FileSystem;

class CatalogLanguageLoader extends BaseLanguageLoader implements LanguageLoaderInterface
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



}