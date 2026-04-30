<?php
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\PageLoader;

use Zencart\FileSystem\FileSystem as FileSystem;
use Zencart\ResourceLoaders\TemplateResolver;
use Zencart\Traits\Singleton;

/**
 * @since ZC v1.5.7
 */
class PageLoader
{
    use Singleton;

    private array $installedPlugins;
    private string $mainPage;
    private FileSystem $fileSystem;
    private ?TemplateResolver $templateResolver = null;

    /**
     * @since ZC v1.5.8
     */
    public function init(
        array $installedPlugins,
        string $mainPage,
        FileSystem $fileSystem,
        ?TemplateResolver $templateResolver = null
    ): void
    {
        $this->installedPlugins = $installedPlugins;
        $this->mainPage = $mainPage;
        $this->fileSystem = $fileSystem;
        $this->templateResolver = $templateResolver;
    }

    // -----
    // This method locates the 'base' module-page directory, either in the
    // storefront's /includes/modules/pages or in an encapsulated plugin's
    // /catalog/includes/modules/pages directory.
    //
    /**
     * @since ZC v1.5.7
     */
    public function findModulePageDirectory(string $context = 'catalog'): bool|string
    {
        if (is_dir(DIR_WS_MODULES . 'pages/' . $this->mainPage)) {
            return DIR_WS_MODULES . 'pages/' . $this->mainPage;
        }
        foreach ($this->installedPlugins as $plugin) {
            $rootDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context;
            $checkDir = $rootDir . '/includes/modules/pages/' . $this->mainPage;
            if (is_dir($checkDir)) {
                return $checkDir;
            }
        }
        return false;
    }

    // -----
    // This method locates **all** files matching a given pattern from the 'base'
    // module-page directory and any module-page directories found in zc_plugins.
    //
    /**
     * @since ZC v2.2.0
     */
    public function listModulePagesFiles(string $nameStartsWith, string $fileExtension = '.php', string $context = 'catalog'): array
    {
        $module_page_dir = DIR_WS_MODULES . 'pages/' . $this->mainPage;
        $fileRegx = '~^' . $nameStartsWith . '.*\\' . $fileExtension . '$~i';
        $fileList = $this->fileSystem->listFilesFromDirectoryAlphaSorted($module_page_dir, $fileRegx, true);
        foreach ($this->installedPlugins as $plugin) {
            $rootDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context;
            $checkDir = $rootDir . '/' . $module_page_dir;
            $fileList = array_merge($fileList, $this->fileSystem->listFilesFromDirectoryAlphaSorted($checkDir, $fileRegx, true));
        }
        return $fileList;
    }

    /**
     * @since ZC v1.5.7
     */
    public function getTemplatePart(string $pageDirectory, string $templatePart, string $fileExtension = '.php'): array
    {
        if ($this->isTemplatePath($pageDirectory)) {
            $directoryArray = [];
            foreach ($this->getTemplateSearchDirectoriesFromPath($pageDirectory) as $directory) {
                $directoryArray = $this->getTemplatePartFromDirectory(
                    $directoryArray,
                    $directory,
                    $templatePart,
                    $fileExtension
                );
            }
            $directoryArray = array_values(array_unique($directoryArray));
            sort($directoryArray);
            return $directoryArray;
        }

        $directoryArray = $this->getTemplatePartFromDirectory(
            [],
            $pageDirectory,
            $templatePart,
            $fileExtension
        );

        foreach ($this->installedPlugins as $plugin) {
            $checkDir = 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/';
            $checkDir .= $pageDirectory;
            $directoryArray = $this->getTemplatePartFromDirectory(
                $directoryArray,
                $checkDir,
                $templatePart,
                $fileExtension
            );
        }
        sort($directoryArray);
        return $directoryArray;
    }

    /**
     * @since ZC v1.5.7
     */
    public function getTemplatePartFromDirectory(array $directoryArray, string $pageDirectory, string $templatePart, string $fileExtension): array
    {
        if ($dir = @dir($pageDirectory)) {
            while ($file = $dir->read()) {
                if (!is_dir($pageDirectory . $file)) {
                    if (substr($file, strrpos($file, '.')) === $fileExtension && preg_match($templatePart, $file)) {
                        $directoryArray[] = $file;
                    }
                }
            }
            $dir->close();
        }
        return $directoryArray;
    }

    /**
     * @since ZC v1.5.8
     */
    function getTemplateDirectory(string $templateCode, string $currentTemplate, string $currentPage, string $templateDir): string
    {
        $templateCode = preg_replace('/\//', '', $templateCode);
        foreach ($this->getTemplateSearchDirectories($this->getCurrentTemplateKey($currentTemplate), $currentPage, $templateDir) as $directory) {
            if ($this->fileSystem->fileExistsInDirectory($directory, $templateCode)) {
                return rtrim($directory, '/');
            }
        }
        return rtrim(DIR_WS_TEMPLATES . 'template_default/' . $templateDir, '/');
    }

    /**
     * @since ZC v1.5.7
     */
    public function getTemplatePluginDir(string $templateCode, string $templateDir, ?string $whichPlugin = ''): bool|string
    {
        foreach ($this->installedPlugins as $plugin) {
            if (!empty($whichPlugin) && $plugin['unique_key'] !== $whichPlugin) {
                continue;
            }

            foreach ($this->getPluginOverlayDirectories($plugin, $templateDir) as $checkDir) {
                if ($this->fileSystem->fileExistsInDirectory($checkDir, preg_replace('/\//', '', $templateCode))) {
                    return $checkDir;
                }
            }
        }
        return false;
    }

    /**
     * @since ZC v1.5.7
     */
    public function getBodyCode(): string
    {
        if (file_exists(DIR_WS_MODULES . 'pages/' . $this->mainPage . '/main_template_vars.php')) {
            return DIR_WS_MODULES . 'pages/' . $this->mainPage . '/main_template_vars.php';
        }
        return $this->getTemplateDirectory('tpl_' . preg_replace('/.php/', '', $this->mainPage) . '_default.php', DIR_WS_TEMPLATE, $this->mainPage, 'templates') . '/tpl_' . $this->mainPage . '_default.php';
    }

    /**
     * @since ZC v3.0.0
     */
    private function getTemplateSearchDirectories(string $templateKey, string $currentPage, string $templateDir): array
    {
        $directories = [];
        $inheritanceChain = $this->getNonDefaultInheritanceChain($templateKey);

        foreach ($inheritanceChain as $chainTemplateKey) {
            $directories = array_merge(
                $directories,
                $this->getCoreTemplateDirectories($chainTemplateKey, $currentPage, $templateDir),
                $this->getOverlayDirectoriesForTarget($chainTemplateKey, $templateDir)
            );
        }

        $directories = array_merge(
            $directories,
            $this->getOverlayDirectoriesForTarget('default', $templateDir),
            $this->getCoreTemplateDirectories('template_default', $currentPage, $templateDir)
        );

        return array_values(array_unique(array_filter($directories)));
    }

    /**
     * @since ZC v3.0.0
     */
    private function getTemplateSearchDirectoriesFromPath(string $pageDirectory): array
    {
        $normalized = $this->normalizeDirectory($pageDirectory);
        if (!preg_match('~includes/templates/([^/]+)/(.+)$~', $normalized, $matches)) {
            return [$pageDirectory];
        }

        $templateKey = $matches[1];
        $templateDir = trim($matches[2], '/');
        return $this->getTemplateSearchDirectories($templateKey, $this->mainPage, $templateDir);
    }

    /**
     * @since ZC v3.0.0
     */
    private function getCoreTemplateDirectories(string $templateKey, string $currentPage, string $templateDir): array
    {
        $record = $this->getTemplateResolver()->getTemplateRecord($templateKey);
        if ($record === null) {
            return [];
        }

        $templateRoot = $this->getRelativeCatalogPath($record['template_path']);
        if ($templateRoot === null) {
            return [];
        }

        return [
            $templateRoot . '/' . trim($currentPage, '/') . '/',
            $templateRoot . '/' . trim($templateDir, '/') . '/',
        ];
    }

    /**
     * @since ZC v3.0.0
     */
    private function getOverlayDirectoriesForTarget(string $targetTemplate, string $templateDir): array
    {
        $directories = [];
        foreach ($this->installedPlugins as $plugin) {
            foreach ($this->getPluginOverlayDirectories($plugin, $templateDir, [$targetTemplate]) as $directory) {
                $directories[] = $directory;
            }
        }
        return $directories;
    }

    /**
     * @since ZC v3.0.0
     */
    private function getPluginOverlayDirectories(array $plugin, string $templateDir, ?array $targets = null): array
    {
        $templatesRoot = 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/templates/';
        if (!is_dir(DIR_FS_CATALOG . $templatesRoot)) {
            return [];
        }

        $availableTargets = $targets ?? $this->getPluginTemplateTargets($templatesRoot);
        $directories = [];
        foreach ($availableTargets as $target) {
            $directory = $templatesRoot . trim($target, '/') . '/' . trim($templateDir, '/') . '/';
            if (is_dir(DIR_FS_CATALOG . $directory)) {
                $directories[] = $directory;
            }
        }

        return $directories;
    }

    /**
     * @since ZC v3.0.0
     */
    private function getPluginTemplateTargets(string $templatesRoot): array
    {
        $targets = [];
        $directory = new \DirectoryIterator(DIR_FS_CATALOG . $templatesRoot);
        foreach ($directory as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }

            $targets[] = $fileInfo->getFilename();
        }

        return $targets;
    }

    /**
     * @since ZC v3.0.0
     */
    private function getTemplateResolver(): TemplateResolver
    {
        if ($this->templateResolver === null) {
            $this->templateResolver = new TemplateResolver();
        }

        return $this->templateResolver;
    }

    /**
     * @since ZC v3.0.0
     */
    private function getCurrentTemplateKey(string $currentTemplate): string
    {
        $normalized = trim($this->normalizeDirectory($currentTemplate), '/');
        if ($normalized === '' || $normalized === 'template_default') {
            return 'template_default';
        }

        if (preg_match('~includes/templates/([^/]+)$~', $normalized, $matches)) {
            return $matches[1];
        }

        return basename($normalized);
    }

    /**
     * @since ZC v3.0.0
     */
    private function getNonDefaultInheritanceChain(string $templateKey): array
    {
        $chain = $this->getTemplateResolver()->getTemplateInheritanceChain($templateKey);
        return array_values(array_filter($chain, static fn(string $item): bool => $item !== 'template_default'));
    }

    /**
     * @since ZC v3.0.0
     */
    private function getRelativeCatalogPath(string $path): ?string
    {
        $normalizedCatalogRoot = rtrim(str_replace('\\', '/', DIR_FS_CATALOG), '/');
        $normalizedPath = $this->normalizeDirectory($path);
        if (!str_starts_with($normalizedPath, $normalizedCatalogRoot . '/')) {
            return null;
        }

        return substr($normalizedPath, strlen($normalizedCatalogRoot) + 1);
    }

    /**
     * @since ZC v3.0.0
     */
    private function isTemplatePath(string $path): bool
    {
        return str_contains($this->normalizeDirectory($path), 'includes/templates/');
    }

    /**
     * @since ZC v3.0.0
     */
    private function normalizeDirectory(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
