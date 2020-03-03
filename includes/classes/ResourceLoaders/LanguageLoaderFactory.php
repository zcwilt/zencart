<?php

namespace Zencart\LanguageLoader;

class LanguageLoaderFactory
{

    public function make($context)
    {
        $class = ucfirst($context) . 'LanguageLoader';
        $classPath = DIR_FS_CATALOG . DIR_WS_CLASSES . 'ResourceLoaders/' . $class;
        if (!file_exists($classPath)) {
            die($classPath);
        }
    }
}