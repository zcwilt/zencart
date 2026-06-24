<?php

class ZenAiAssistContentRegistry
{
    private string $projectRoot;
    private string $bundledPluginRoot;
    private ?ZenAiAssistRuntimeInspector $runtimeInspector;

    public function __construct(string $projectRoot, string $bundledPluginRoot, ?ZenAiAssistRuntimeInspector $runtimeInspector = null)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\') . '/';
        $this->bundledPluginRoot = rtrim($bundledPluginRoot, '/\\') . '/';
        $this->runtimeInspector = $runtimeInspector;
    }

    public function guidanceSources(): array
    {
        return array_values(array_filter(array_map(function (array $source): ?array {
            $guidanceDirectory = $source['root'] . $source['guidance_relative_path'];
            if (!is_dir($guidanceDirectory)) {
                return null;
            }

            $source['guidance_dir'] = $guidanceDirectory;

            return $source;
        }, $this->contentSources())));
    }

    public function skillSources(): array
    {
        return array_values(array_filter(array_map(function (array $source): ?array {
            $skillsDirectory = $source['root'] . $source['skills_relative_path'];
            if (!is_dir($skillsDirectory)) {
                return null;
            }

            $source['skills_dir'] = $skillsDirectory;

            return $source;
        }, $this->contentSources())));
    }

    private function contentSources(): array
    {
        $sources = [];

        $coreRoot = $this->projectRoot . 'includes/zen_ai_assist/';
        if (is_dir($coreRoot)) {
            $sources[] = [
                'id' => 'core',
                'type' => 'core',
                'label' => 'Core Zen Cart',
                'root' => $coreRoot,
                'guidance_relative_path' => 'guidance/',
                'skills_relative_path' => 'skills/',
            ];
        }

        foreach ($this->pluginContentSources() as $pluginSource) {
            $sources[] = $pluginSource;
        }

        $sources[] = [
            'id' => 'bundled',
            'type' => 'bundled',
            'label' => 'Zen AI Assist Bundled',
            'root' => $this->bundledPluginRoot . 'resources/',
            'guidance_relative_path' => 'guidance/',
            'skills_relative_path' => 'skills/',
            'plugin' => [
                'unique_key' => basename(dirname(rtrim($this->bundledPluginRoot, '/\\'))),
                'version' => basename(rtrim($this->bundledPluginRoot, '/\\')),
            ],
        ];

        return $sources;
    }

    private function pluginContentSources(): array
    {
        if ($this->runtimeInspector === null) {
            return [];
        }

        $installedPlugins = $this->runtimeInspector->listInstalledPlugins('all');
        if (($installedPlugins['runtime_state'] ?? '') !== 'available') {
            return [];
        }

        $sources = [];
        foreach ($installedPlugins['plugins'] ?? [] as $plugin) {
            if (!is_array($plugin)) {
                continue;
            }

            $manifestPath = trim((string)($plugin['manifest_path'] ?? ''));
            if ($manifestPath === '') {
                continue;
            }

            $pluginRoot = $this->projectRoot . dirname($manifestPath) . '/';
            if (!is_dir($pluginRoot)) {
                continue;
            }

            if (realpath($pluginRoot) === realpath($this->bundledPluginRoot)) {
                continue;
            }

            $contentRoot = $pluginRoot . 'resources/zen_ai_assist/';
            if (!is_dir($contentRoot)) {
                continue;
            }

            $sources[] = [
                'id' => 'plugin:' . (string)($plugin['unique_key'] ?? 'unknown') . '@' . (string)($plugin['version'] ?? 'unknown'),
                'type' => 'plugin',
                'label' => (string)($plugin['name'] ?? ($plugin['unique_key'] ?? 'Plugin')),
                'root' => $contentRoot,
                'guidance_relative_path' => 'guidance/',
                'skills_relative_path' => 'skills/',
                'plugin' => [
                    'unique_key' => (string)($plugin['unique_key'] ?? ''),
                    'version' => (string)($plugin['version'] ?? ''),
                    'status' => (string)($plugin['status'] ?? ''),
                ],
            ];
        }

        usort($sources, static function (array $left, array $right): int {
            return [(string)$left['label'], (string)$left['id']] <=> [(string)$right['label'], (string)$right['id']];
        });

        return $sources;
    }
}
