<?php


namespace Zencart\LanguageLoader;


class BaseLanguageLoader
{
    public function __construct($pluginList, $currentPage)
    {
//        $this->pluginList = $pluginList;
        $this->pluginList = []; // @todo temp
        $this->currentPage = $currentPage;
    }


}