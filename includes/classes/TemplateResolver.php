<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\TemplateResolver;

/**
 * @since ZC v3.0.0
 */
class TemplateResolver
{
    private string $catalogRoot;
    private string $coreTemplatesPath;
    private string $pluginsRoot;
    private array $templateRecords = [];

    /**
     * @since ZC v3.0.0
     */
    public function __construct(?string $catalogRoot = null, ?string $coreTemplatesPath = null, ?string $pluginsRoot = null)
    {
        $this->catalogRoot = $this->normalizeDirectory($catalogRoot ?? (defined('DIR_FS_CATALOG') ? DIR_FS_CATALOG : dirname(__DIR__, 2)));
        $this->coreTemplatesPath = $this->normalizeDirectory($coreTemplatesPath ?? $this->catalogRoot . '/includes/templates');
        $this->pluginsRoot = $this->normalizeDirectory($pluginsRoot ?? $this->catalogRoot . '/zc_plugins');
    }

    /**
     * @since ZC v3.0.0
     */
    public function getSelectableTemplates(bool $includeTemplateDefault = false): array
    {
        $templates = $this->getTemplateRecords();
        if ($includeTemplateDefault) {
            return $templates;
        }

        unset($templates['template_default']);
        return $templates;
    }

    /**
     * @since ZC v3.0.0
     */
    public function getTemplateRecord(string $templateKey): ?array
    {
        $templates = $this->getTemplateRecords();
        return $templates[$templateKey] ?? null;
    }

    /**
     * @since ZC v3.0.0
     */
    public function getTemplateFilesystemPath(string $templateKey): ?string
    {
        return $this->getTemplateRecord($templateKey)['template_path'] ?? null;
    }

    /**
     * @since ZC v3.0.0
     */
    public function getTemplateCatalogPath(string $templateKey): ?string
    {
        return $this->getTemplateRecord($templateKey)['template_catalog_path'] ?? null;
    }

    /**
     * @since ZC v3.0.0
     */
    public function getTemplateWebPath(string $templateKey): ?string
    {
        $record = $this->getTemplateRecord($templateKey);
        return $record['template_web_path'] ?? null;
    }

    /**
     * @since ZC v3.0.0
     */
    public function getBaseTemplate(string $templateKey): string
    {
        $record = $this->getTemplateRecord($templateKey);
        if ($record === null) {
            return 'template_default';
        }

        return $record['base_template'] ?? 'template_default';
    }

    /**
     * @since ZC v3.0.0
     */
    public function getTemplateInheritanceChain(string $templateKey): array
    {
        $chain = [];
        $seen = [];
        $currentTemplate = $templateKey;

        while (!empty($currentTemplate) && !isset($seen[$currentTemplate])) {
            $record = $this->getTemplateRecord($currentTemplate);
            if ($record === null) {
                break;
            }

            $chain[] = $currentTemplate;
            $seen[$currentTemplate] = true;

            $baseTemplate = $record['base_template'] ?? null;
            if (empty($baseTemplate) || $baseTemplate === $currentTemplate) {
                break;
            }

            $currentTemplate = $baseTemplate;
        }

        if (!in_array('template_default', $chain, true) && $this->getTemplateRecord('template_default') !== null) {
            $chain[] = 'template_default';
        }

        return $chain;
    }

    /**
     * @since ZC v3.0.0
     */
    public function isPluginTemplate(string $templateKey): bool
    {
        $record = $this->getTemplateRecord($templateKey);
        return !empty($record['is_plugin_template']);
    }

    /**
     * @since ZC v3.0.0
     */
    private function getTemplateRecords(): array
    {
        if ($this->templateRecords === []) {
            $this->templateRecords = array_merge(
                $this->loadCoreTemplates(),
                $this->loadPluginTemplates()
            );
        }

        return $this->templateRecords;
    }

    /**
     * @since ZC v3.0.0
     */
    private function loadCoreTemplates(): array
    {
        $templates = [];
        if (!is_dir($this->coreTemplatesPath)) {
            return $templates;
        }

        $dir = new \DirectoryIterator($this->coreTemplatesPath);
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }

            $templateKey = $fileInfo->getFilename();
            $templatePath = $this->normalizeDirectory($fileInfo->getPathname());
            $templateInfo = $this->loadTemplateInfo($templatePath . '/template_info.php');
            if ($templateInfo === null) {
                continue;
            }

            $templates[$templateKey] = array_merge($templateInfo, [
                'template_key' => $templateKey,
                'template_path' => $templatePath . '/',
                'template_catalog_path' => 'includes/templates/' . $templateKey . '/',
                'template_web_path' => $this->buildCoreWebPath($templateKey),
                'template_settings_path' => $templatePath . '/template_settings.php',
                'base_template' => $templateKey === 'template_default' ? 'template_default' : 'template_default',
                'is_plugin_template' => false,
                'template_source' => 'core',
            ]);
        }

        return $templates;
    }

    /**
     * @since ZC v3.0.0
     */
    private function loadPluginTemplates(): array
    {
        $templates = [];
        if (!is_dir($this->pluginsRoot)) {
            return $templates;
        }

        $pluginsDir = new \DirectoryIterator($this->pluginsRoot);
        foreach ($pluginsDir as $pluginDir) {
            if ($pluginDir->isDot() || !$pluginDir->isDir()) {
                continue;
            }

            $versionsDir = new \DirectoryIterator($pluginDir->getPathname());
            foreach ($versionsDir as $versionDir) {
                if ($versionDir->isDot() || !$versionDir->isDir()) {
                    continue;
                }

                $manifestFile = $versionDir->getPathname() . '/manifest.php';
                if (!file_exists($manifestFile)) {
                    continue;
                }

                $manifest = require $manifestFile;
                if (!$this->isSelectableTemplateManifest($manifest)) {
                    continue;
                }

                $templateRecord = $this->buildPluginTemplateRecord(
                    $pluginDir->getFilename(),
                    $versionDir->getFilename(),
                    $versionDir->getPathname(),
                    $manifest
                );

                if ($templateRecord === null) {
                    continue;
                }

                $templates[$templateRecord['template_key']] = $templateRecord;
            }
        }

        return $templates;
    }

    /**
     * @since ZC v3.0.0
     */
    private function isSelectableTemplateManifest(mixed $manifest): bool
    {
        if (!is_array($manifest) || empty($manifest['template']) || !is_array($manifest['template'])) {
            return false;
        }

        $capabilities = $manifest['pluginCapabilities'] ?? [];
        if (!is_array($capabilities) || !in_array('template', $capabilities, true)) {
            return false;
        }

        return ($manifest['template']['type'] ?? null) === 'selectable'
            && !empty($manifest['template']['key']);
    }

    /**
     * @since ZC v3.0.0
     */
    private function buildPluginTemplateRecord(string $pluginKey, string $pluginVersion, string $versionPath, array $manifest): ?array
    {
        $template = $manifest['template'];
        $templateKey = $template['key'];
        $defaultTemplatePath = $this->normalizeDirectory($versionPath . '/catalog/includes/templates/' . $templateKey);
        $templateInfoFile = !empty($template['infoFile'])
            ? $versionPath . '/' . ltrim($template['infoFile'], '/')
            : $defaultTemplatePath . '/template_info.php';
        $templateInfo = $this->loadTemplateInfo($templateInfoFile);
        if ($templateInfo === null) {
            return null;
        }

        $templatePath = $this->normalizeDirectory(dirname($templateInfoFile)) . '/';
        $templateCatalogPath = ltrim(str_replace($this->normalizeDirectory($this->catalogRoot) . '/', '', $this->normalizeDirectory($templatePath)), '/') . '/';
        $settingsFile = !empty($template['settingsFile'])
            ? $versionPath . '/' . ltrim($template['settingsFile'], '/')
            : $templatePath . 'template_settings.php';

        return array_merge($templateInfo, [
            'template_key' => $templateKey,
            'template_path' => $templatePath,
            'template_catalog_path' => $templateCatalogPath,
            'template_web_path' => $this->buildPluginWebPath($templateCatalogPath),
            'template_settings_path' => $settingsFile,
            'base_template' => $template['baseTemplate'] ?? 'template_default',
            'is_plugin_template' => true,
            'template_source' => 'plugin',
            'plugin_key' => $pluginKey,
            'plugin_version' => $pluginVersion,
            'manifest' => $manifest,
            'has_template_settings' => file_exists($settingsFile),
        ]);
    }

    /**
     * @since ZC v3.0.0
     */
    private function loadTemplateInfo(string $templateInfoFile): ?array
    {
        if (!file_exists($templateInfoFile)) {
            return null;
        }

        $template_name = null;
        $template_version = null;
        $template_author = null;
        $template_description = null;
        $template_screenshot = null;
        $uses_single_column_layout_settings = false;
        $uses_mobile_sidebox_settings = true;

        require $templateInfoFile;

        return [
            'name' => $template_name,
            'version' => $template_version,
            'author' => $template_author,
            'description' => $template_description,
            'screenshot' => $template_screenshot,
            'uses_single_column_layout_settings' => !empty($uses_single_column_layout_settings),
            'uses_mobile_sidebox_settings' => !isset($uses_mobile_sidebox_settings) || !empty($uses_mobile_sidebox_settings),
            'has_template_settings' => file_exists(dirname($templateInfoFile) . '/template_settings.php'),
        ];
    }

    /**
     * @since ZC v3.0.0
     */
    private function buildCoreWebPath(string $templateKey): string
    {
        $catalogWebRoot = defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/';
        return rtrim($catalogWebRoot, '/') . '/includes/templates/' . $templateKey . '/';
    }

    /**
     * @since ZC v3.0.0
     */
    private function buildPluginWebPath(string $templateCatalogPath): string
    {
        $catalogWebRoot = defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/';
        return rtrim($catalogWebRoot, '/') . '/' . trim($templateCatalogPath, '/') . '/';
    }

    /**
     * @since ZC v3.0.0
     */
    private function normalizeDirectory(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
