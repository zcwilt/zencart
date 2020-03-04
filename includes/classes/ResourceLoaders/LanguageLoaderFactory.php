<?php

namespace Zencart\LanguageLoader;

class LanguageLoaderFactory
{

    public function make($context, $pluginList, $currentPage)
    {
        $class = ucfirst($context) . 'LanguageLoader';
        $classPath = DIR_FS_CATALOG . DIR_WS_CLASSES . 'ResourceLoaders/' . $class . '.php';
        if (!file_exists($classPath)) {
            die($classPath);
        }
        $class = __NAMESPACE__ . '\\' . $class;
        $obj = new $class($pluginList, $currentPage);
        return $obj;
    }
}