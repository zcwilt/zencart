<?php
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\LanguageLoader;

use Zencart\FileSystem\FileSystem;
use Zencart\TemplateResolver\TemplateResolver;

/**
 * @since ZC v1.5.8
 */
class BaseLanguageLoader
{
    protected string $fallback;
    protected \Zencart\FileSystem\FileSystem $fileSystem;
    protected array $languageDefines = [];
    protected array $pluginList;
    protected string $templateDir;
    protected string $zcPluginsDir;
    protected TemplateResolver $templateResolver;

    public string $currentPage;

    public function __construct(array $pluginList, string $currentPage, string $templateDir, string $fallback = 'english')
    {
        $this->pluginList = $pluginList;
        $this->currentPage = $currentPage;
        $this->fallback = $fallback;
        $this->fileSystem = new FileSystem();
        $this->templateDir = $templateDir;
        $this->zcPluginsDir = DIR_FS_CATALOG . 'zc_plugins/';
        $this->templateResolver = new TemplateResolver();
    }

    /**
     * @since ZC v2.2.0
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    /**
     * @since ZC v2.2.0
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getTemplateInheritanceChainForLookup(bool $reverse = false): array
    {
        $chain = $this->templateResolver->getTemplateInheritanceChain($this->templateDir);
        if ($chain === []) {
            $chain = [$this->templateDir];
        }

        if ($reverse) {
            $chain = array_reverse($chain);
        }

        return array_values(array_unique($chain));
    }

    /**
     * @since ZC v2.2.1
     */
    protected function findTemplateLanguageOverrideFile(
        string $rootPath,
        string $language,
        string $fileName,
        string $extraPath = ''
    ): ?string {
        $rootPath = rtrim($rootPath, '/') . '/';
        $extraPath = trim($extraPath, '/');
        foreach ($this->getTemplateInheritanceChainForLookup() as $templateKey) {
            $path = $rootPath . $language . '/';
            if ($extraPath !== '') {
                $path .= $extraPath . '/';
            }
            $path .= $templateKey . '/' . $fileName;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function findTemplateFirstLanguageFile(string $rootPath, string $fileName): ?string
    {
        $rootPath = rtrim($rootPath, '/') . '/';
        foreach ($this->getTemplateInheritanceChainForLookup() as $templateKey) {
            $path = $rootPath . $templateKey . '/' . $fileName;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getTemplateLanguageOverrideFiles(
        string $rootPath,
        string $language,
        string $fileName,
        string $extraPath = ''
    ): array {
        $rootPath = rtrim($rootPath, '/') . '/';
        $extraPath = trim($extraPath, '/');
        $files = [];

        foreach ($this->getTemplateInheritanceChainForLookup(true) as $templateKey) {
            $path = $rootPath . $language . '/';
            if ($extraPath !== '') {
                $path .= $extraPath . '/';
            }
            $path .= $templateKey . '/' . $fileName;
            if (is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getTemplateFirstLanguageFiles(string $rootPath, string $fileName): array
    {
        $rootPath = rtrim($rootPath, '/') . '/';
        $files = [];
        foreach ($this->getTemplateInheritanceChainForLookup(true) as $templateKey) {
            $path = $rootPath . $templateKey . '/' . $fileName;
            if (is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }
}
