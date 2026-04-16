<?php
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\ResourceLoaders;

/**
 * @since ZC v1.5.8
 */
class SideboxFinder
{
    private $filesystem;
    private TemplateResolver $templateResolver;
    private string $catalogRoot;

    public function __construct($filesystem, ?TemplateResolver $templateResolver = null, ?string $catalogRoot = null)
    {
        $this->filesystem = $filesystem;
        $this->templateResolver = $templateResolver ?? new TemplateResolver();
        $this->catalogRoot = rtrim($catalogRoot ?? DIR_FS_CATALOG, '/') . '/';
    }

    /**
     * @since ZC v1.5.8
     */
    public function findFromFilesystem(array $installedPlugins, string $templateDir): array
    {
        $sideboxes = [];
        foreach ($installedPlugins as $plugin) {
            $pluginDir = $this->catalogRoot . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/modules/sideboxes/';
            $files = $this->filesystem->listFilesFromDirectoryAlphaSorted($pluginDir);
            foreach ($files as $file) {
                $sideboxes[$file] = $plugin['unique_key'] . '/' . $plugin['version'];
            }
        }
        $mainDir = $this->catalogRoot . DIR_WS_MODULES . 'sideboxes/';
        $files = $this->filesystem->listFilesFromDirectoryAlphaSorted($mainDir);
        foreach ($files as $file) {
            $sideboxes[$file] = '';
        }

        foreach ($this->getTemplateSpecificSideboxDirectories($templateDir) as $templateSpecificDir) {
            $files = $this->filesystem->listFilesFromDirectoryAlphaSorted($templateSpecificDir['full_path']);
            foreach ($files as $file) {
                $sideboxes[$file] = $templateSpecificDir['plugin_details'];
            }
        }
        return $sideboxes;
    }

    /**
     * @since ZC v1.5.8
     */
    public function sideboxPath($sideboxInfo, string $templateDir, bool $withFullPath = false): bool|string
    {
        if (!empty($sideboxInfo['plugin_details'])) {
            $path = $this->sideboxPathInPlugin($sideboxInfo);
            if ($path !== false) {
                return ($withFullPath) ? $this->catalogRoot . 'zc_plugins/' . $path . '/catalog/includes/modules/sideboxes/' : ($path . '/');
            }
        }

        foreach ($this->getTemplateSpecificSideboxDirectories($templateDir) as $templateSpecificDir) {
            if (file_exists($templateSpecificDir['full_path'] . $sideboxInfo['layout_box_name'])) {
                return $withFullPath ? $templateSpecificDir['full_path'] : $templateSpecificDir['relative_path'];
            }
        }

        $baseDir = $this->catalogRoot . DIR_WS_MODULES . 'sideboxes/';
        $rootPath = ($withFullPath) ? $this->catalogRoot . DIR_WS_MODULES : '';
        if (file_exists($baseDir . $sideboxInfo['layout_box_name'])) {
            return $rootPath . 'sideboxes/';
        }
        return false;
    }

    /**
     * @since ZC v1.5.8
     */
    public function sideboxPathInPlugin($sideboxInfo): bool|string
    {
        $baseDir = $this->catalogRoot . 'zc_plugins/' . $sideboxInfo['plugin_details'] . '/'  . 'catalog/includes/modules/sideboxes/';
        if (file_exists($baseDir . $sideboxInfo['layout_box_name'])) {
            return $sideboxInfo['plugin_details'];
        }
        return false;
    }

    /**
     * @since ZC v2.2.1
     */
    protected function getTemplateSpecificSideboxDirectories(string $templateDir): array
    {
        $directories = [];
        $chain = $this->templateResolver->getTemplateInheritanceChain($templateDir);
        if ($chain === []) {
            $chain = [$templateDir];
        }

        foreach (array_reverse($chain) as $templateKey) {
            $record = $this->templateResolver->getTemplateRecord($templateKey);
            if ($record === null) {
                continue;
            }

            if (!empty($record['is_plugin_template'])) {
                $relativePath = 'zc_plugins/' . $record['plugin_key'] . '/' . $record['plugin_version'] . '/catalog/includes/modules/sideboxes/' . $templateKey . '/';
                $directories[] = [
                    'full_path' => $this->catalogRoot . $relativePath,
                    'relative_path' => $relativePath,
                    'plugin_details' => $record['plugin_key'] . '/' . $record['plugin_version'],
                ];
                continue;
            }

            $relativePath = 'includes/modules/sideboxes/' . $templateKey . '/';
            $directories[] = [
                'full_path' => $this->catalogRoot . $relativePath,
                'relative_path' => $relativePath,
                'plugin_details' => '',
            ];
        }

        return $directories;
    }
}
