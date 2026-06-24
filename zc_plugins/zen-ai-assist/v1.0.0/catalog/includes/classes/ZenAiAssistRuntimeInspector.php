<?php

class ZenAiAssistRuntimeInspector
{
    private string $projectRoot;
    private string $pluginRoot;

    public function __construct(string $projectRoot, string $pluginRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\') . '/';
        $this->pluginRoot = rtrim($pluginRoot, '/\\') . '/';
    }

    public function inspectBootstrapLoaders(): array
    {
        $catalogAutoLoaders = $this->listFiles('includes/auto_loaders');
        $catalogInitIncludes = $this->listFiles('includes/init_includes');
        $adminAutoLoaders = $this->listFiles('admin/includes/auto_loaders');
        $adminInitIncludes = $this->listFiles('admin/includes/init_includes');
        $pluginLoaderFiles = $this->listFilesRelativeToPlugin(['catalog', 'admin'], [
            'extra_configures',
            'extra_datafiles',
            'init_includes',
            'auto_loaders',
            'filenames.php',
        ]);

        return [
            'project_root' => $this->projectRoot,
            'plugin_root' => $this->pluginRoot,
            'catalog' => [
                'auto_loaders' => $catalogAutoLoaders,
                'init_includes' => $catalogInitIncludes,
            ],
            'admin' => [
                'auto_loaders' => $adminAutoLoaders,
                'init_includes' => $adminInitIncludes,
            ],
            'plugin_inputs' => $pluginLoaderFiles,
        ];
    }

    public function lookupFilenameConstant(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['query' => $query, 'matches' => []];
        }

        $matches = [];
        foreach ($this->filenameDefinitionFiles() as $path) {
            $contents = @file_get_contents($path);
            if (!is_string($contents) || $contents === '') {
                continue;
            }

            if (!preg_match_all('/define\(\s*[\'"](FILENAME_[A-Z0-9_]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $contents, $rows, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($rows as $row) {
                $constant = $row[1];
                $value = $row[2];
                if (
                    stripos($constant, $query) === false
                    && stripos($value, $query) === false
                ) {
                    continue;
                }

                $matches[] = [
                    'constant' => $constant,
                    'value' => $value,
                    'path' => $this->relativePath($path),
                ];
            }
        }

        return [
            'query' => $query,
            'matches' => $matches,
        ];
    }

    public function listPageModules(string $page): array
    {
        $page = trim($page);
        if ($page === '') {
            return ['page' => $page, 'matches' => []];
        }

        $matches = [];
        foreach ([
            'includes/modules/pages/' . $page,
            'admin/includes/modules/pages/' . $page,
        ] as $relativeDirectory) {
            $absoluteDirectory = $this->projectRoot . $relativeDirectory;
            if (!is_dir($absoluteDirectory)) {
                continue;
            }

            $files = [];
            foreach (glob($absoluteDirectory . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    $files[] = $this->relativePath($file);
                }
            }

            sort($files);

            $matches[] = [
                'directory' => $relativeDirectory,
                'files' => $files,
                'template_candidates' => $this->templateCandidates($page),
            ];
        }

        return [
            'page' => $page,
            'matches' => $matches,
        ];
    }

    public function readRecentLogs(string $pattern = '', int $lineLimit = 40, int $fileLimit = 5): array
    {
        $lineLimit = max(1, $lineLimit);
        $fileLimit = max(1, $fileLimit);
        $pattern = trim($pattern);

        $matches = [];
        foreach ($this->candidateLogFiles() as $path) {
            $basename = basename($path);
            if ($pattern !== '' && stripos($basename, $pattern) === false) {
                continue;
            }

            $lines = @file($path, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                continue;
            }

            $tail = array_slice($lines, -$lineLimit);
            $matches[] = [
                'path' => $this->relativePath($path),
                'modified_at' => gmdate('c', filemtime($path) ?: time()),
                'size_bytes' => filesize($path) ?: 0,
                'tail' => $tail,
            ];

            if (count($matches) >= $fileLimit) {
                break;
            }
        }

        return [
            'pattern' => $pattern,
            'line_limit' => $lineLimit,
            'file_limit' => $fileLimit,
            'matches' => $matches,
        ];
    }

    public function buildRuntimeContext(
        string $runtimeState,
        array $warnings = [],
        ?bool $inspectionAvailable = null,
        ?string $detail = null
    ): array
    {
        $warnings = $this->normalizeWarnings($warnings);
        $inspectionAvailable ??= $runtimeState === 'available';
        $detail ??= $this->runtimeStateDetail($runtimeState, $warnings);

        return [
            'state' => $runtimeState,
            'category' => $this->runtimeStateCategory($runtimeState),
            'inspection_available' => $inspectionAvailable,
            'message' => $this->describeRuntimeState($runtimeState),
            'detail' => $detail,
            'warnings' => $warnings,
            'recommended_action' => $this->runtimeStateRecommendation($runtimeState),
        ];
    }

    public function listInstalledPlugins(string $statusFilter = 'all'): array
    {
        $statusFilter = strtolower(trim($statusFilter));
        $statusMap = [
            'all' => null,
            'enabled' => 1,
            'disabled' => 2,
            'not-installed' => 0,
            'not_installed' => 0,
        ];

        if (!array_key_exists($statusFilter, $statusMap)) {
            $runtimeState = 'invalid-filter';
            $runtimeContext = $this->buildRuntimeContext(
                $runtimeState,
                ['Unsupported status filter. Use all, enabled, disabled, or not-installed.'],
                false,
                'Unsupported status filter.'
            );

            return [
                'runtime_state' => $runtimeState,
                'runtime_state_category' => $runtimeContext['category'],
                'runtime_state_detail' => $runtimeContext['detail'],
                'inspection_available' => $runtimeContext['inspection_available'],
                'runtime_state_message' => $runtimeContext['message'],
                'runtime_context' => $runtimeContext,
                'status_filter' => $statusFilter,
                'warnings' => $runtimeContext['warnings'],
                'plugins' => [],
            ];
        }

        $context = $this->loadPluginRepositoryContext();
        if (($context['repository'] ?? null) === null) {
            $runtimeState = (string)($context['runtime_state'] ?? 'repository-unavailable');
            $runtimeContext = $this->buildRuntimeContext(
                $runtimeState,
                $context['warnings'] ?? ['Plugin repository context is unavailable.'],
                false
            );

            return [
                'runtime_state' => $runtimeState,
                'runtime_state_category' => $runtimeContext['category'],
                'runtime_state_detail' => $runtimeContext['detail'],
                'inspection_available' => $runtimeContext['inspection_available'],
                'runtime_state_message' => $runtimeContext['message'],
                'runtime_context' => $runtimeContext,
                'status_filter' => $statusFilter,
                'warnings' => $runtimeContext['warnings'],
                'plugins' => [],
            ];
        }

        $repository = $context['repository'];
        $rows = $statusMap[$statusFilter] === null
            ? $repository->getAll()
            : $repository->getInstalledPlugins($statusMap[$statusFilter]);

        $plugins = [];
        foreach ($rows as $row) {
            $uniqueKey = (string)($row['unique_key'] ?? '');
            $version = (string)($row['version'] ?? '');
            $manifestPath = $this->projectRoot . 'zc_plugins/' . $uniqueKey . '/' . $version . '/manifest.php';

            $plugins[] = [
                'unique_key' => $uniqueKey,
                'name' => (string)($row['name'] ?? $uniqueKey),
                'version' => $version,
                'status' => $this->formatPluginStatus((int)($row['status'] ?? 0)),
                'author' => (string)($row['author'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'zc_versions' => (string)($row['zc_versions'] ?? ''),
                'manifest_path' => is_file($manifestPath) ? $this->relativePath($manifestPath) : null,
            ];
        }

        usort($plugins, static function (array $left, array $right): int {
            return [(string)$left['name'], (string)$left['unique_key']] <=> [(string)$right['name'], (string)$right['unique_key']];
        });

        $runtimeState = 'available';
        $runtimeContext = $this->buildRuntimeContext($runtimeState, $context['warnings'] ?? [], true, 'Installed plugin inspection resolved plugin manager state successfully.');

        return [
            'runtime_state' => $runtimeState,
            'runtime_state_category' => $runtimeContext['category'],
            'runtime_state_detail' => $runtimeContext['detail'],
            'inspection_available' => $runtimeContext['inspection_available'],
            'runtime_state_message' => $runtimeContext['message'],
            'runtime_context' => $runtimeContext,
            'status_filter' => $statusFilter,
            'warnings' => $runtimeContext['warnings'],
            'plugins' => $plugins,
        ];
    }

    public function inspectPluginStructure(string $path): array
    {
        $pluginRoot = $this->normalizePluginRoot($path);
        if ($pluginRoot === null) {
            return [
                'plugin_root' => null,
                'findings' => ['The provided path does not resolve to a plugin root.'],
                'manifest_path' => null,
                'filenames' => [],
                'catalog_pages' => [],
                'admin_pages' => [],
                'observers' => [],
                'autoloaders' => [],
                'extra_files' => [],
                'skill_topics' => [],
            ];
        }

        $filenames = $this->pluginFilenameDefinitions($pluginRoot);
        $adminPageRegistrations = $this->adminPageRegistrations($pluginRoot);
        $catalogPages = [];
        $adminPages = [];
        $findings = [];

        $catalogPagesRoot = $pluginRoot . 'catalog/includes/modules/pages/';
        if (is_dir($catalogPagesRoot)) {
            foreach (glob($catalogPagesRoot . '*', GLOB_ONLYDIR) ?: [] as $pageDirectory) {
                $page = basename($pageDirectory);
                $headerPath = $pageDirectory . '/header_php.php';
                $mainTemplateVarsPath = $pageDirectory . '/main_template_vars.php';
                $languagePath = $pluginRoot . 'catalog/includes/languages/english/lang.' . $page . '.php';
                $templateFiles = glob($pluginRoot . 'catalog/includes/templates/*/tpl_' . $page . '*.php') ?: [];
                $filenameMatches = $this->matchFilenameDefinitions($filenames, $page);
                $languageAnalysis = is_file($languagePath) ? $this->analyzeLanguageFile($languagePath, 'page', $page) : null;

                $catalogPages[] = [
                    'page' => $page,
                    'header_php' => is_file($headerPath) ? $this->relativePath($headerPath) : null,
                    'main_template_vars' => is_file($mainTemplateVarsPath) ? $this->relativePath($mainTemplateVarsPath) : null,
                    'language_file' => is_file($languagePath) ? $this->relativePath($languagePath) : null,
                    'language_analysis' => $languageAnalysis,
                    'template_files' => array_map([$this, 'relativePath'], $templateFiles),
                    'filename_constants' => $filenameMatches,
                ];

                if (!is_file($headerPath)) {
                    $findings[] = 'Catalog page `' . $page . '` is missing `header_php.php`.';
                }
                if (!is_file($languagePath)) {
                    $findings[] = 'Catalog page `' . $page . '` is missing `catalog/includes/languages/english/lang.' . $page . '.php`.';
                }
                if ($templateFiles === []) {
                    $findings[] = 'Catalog page `' . $page . '` is missing a matching template file.';
                }
                if ($filenameMatches === []) {
                    $findings[] = 'Catalog page `' . $page . '` has no matching `FILENAME_*` definition in the plugin `filenames.php`.';
                }
                if (is_file($languagePath) && !$this->isReadablePhpIncludeFile($languagePath)) {
                    $findings[] = 'Catalog page `' . $page . '` has an unreadable or malformed language file.';
                }
                if (($languageAnalysis['has_language_definitions'] ?? true) === false) {
                    $findings[] = 'Catalog page `' . $page . '` language file does not define any language keys.';
                }
                if (($languageAnalysis['has_expected_page_keys'] ?? true) === false) {
                    $findings[] = 'Catalog page `' . $page . '` language file does not define any typical page-language keys.';
                }
            }
        }

        $adminRoot = $pluginRoot . 'admin/';
        if (is_dir($adminRoot)) {
            foreach (glob($adminRoot . '*.php') ?: [] as $adminPagePath) {
                $page = basename($adminPagePath, '.php');
                $languagePath = $pluginRoot . 'admin/includes/languages/english/lang.' . $page . '.php';
                $menuDefinitionPath = $pluginRoot . 'admin/includes/languages/english/extra_definitions/lang.' . $page . '_menu.php';
                $filenameMatches = $this->matchFilenameDefinitions($filenames, $page);
                $menuDefinitionKeys = is_file($menuDefinitionPath) ? $this->languageDefinitionKeys($menuDefinitionPath) : [];
                $languageAnalysis = is_file($languagePath) ? $this->analyzeLanguageFile($languagePath, 'admin-page', $page) : null;
                $menuDefinitionAnalysis = is_file($menuDefinitionPath) ? $this->analyzeLanguageFile($menuDefinitionPath, 'menu', $page) : null;
                $installerRegistrations = $this->matchAdminPageRegistrations($adminPageRegistrations, $filenameMatches, $menuDefinitionKeys, $page);

                $adminPages[] = [
                    'page' => $page,
                    'entrypoint' => $this->relativePath($adminPagePath),
                    'language_file' => is_file($languagePath) ? $this->relativePath($languagePath) : null,
                    'language_analysis' => $languageAnalysis,
                    'menu_definition' => is_file($menuDefinitionPath) ? $this->relativePath($menuDefinitionPath) : null,
                    'menu_definition_analysis' => $menuDefinitionAnalysis,
                    'filename_constants' => $filenameMatches,
                    'menu_definition_keys' => $menuDefinitionKeys,
                    'installer_registrations' => $installerRegistrations,
                ];

                if (!is_file($languagePath)) {
                    $findings[] = 'Admin page `' . $page . '` is missing `admin/includes/languages/english/lang.' . $page . '.php`.';
                }
                if (!is_file($menuDefinitionPath)) {
                    $findings[] = 'Admin page `' . $page . '` is missing `admin/includes/languages/english/extra_definitions/lang.' . $page . '_menu.php`.';
                }
                if (is_file($languagePath) && !$this->isReadablePhpIncludeFile($languagePath)) {
                    $findings[] = 'Admin page `' . $page . '` has an unreadable or malformed language file.';
                }
                if (($languageAnalysis['has_language_definitions'] ?? true) === false) {
                    $findings[] = 'Admin page `' . $page . '` language file does not define any language keys.';
                }
                if (($languageAnalysis['has_expected_page_keys'] ?? true) === false) {
                    $findings[] = 'Admin page `' . $page . '` language file does not define any typical admin page-language keys.';
                }
                if (is_file($menuDefinitionPath) && !$this->menuDefinitionLooksValid($menuDefinitionPath, $page)) {
                    $findings[] = 'Admin page `' . $page . '` has a menu-definition file that does not appear to define an encapsulated admin menu label.';
                }
                if (is_file($menuDefinitionPath) && $menuDefinitionKeys === []) {
                    $findings[] = 'Admin page `' . $page . '` has a menu-definition file that does not define any language keys.';
                }
                if (($menuDefinitionAnalysis['has_box_keys'] ?? true) === false) {
                    $findings[] = 'Admin page `' . $page . '` menu-definition file does not define any `BOX_*` language keys.';
                }
                if ($installerRegistrations === []) {
                    $findings[] = 'Admin page `' . $page . '` has no matching installer `zen_register_admin_page()` registration.';
                }
                foreach ($installerRegistrations as $registration) {
                    $languageKey = trim((string)($registration['language_key'] ?? ''));
                    $mainPage = trim((string)($registration['main_page'] ?? ''));

                    if ($languageKey !== '' && $menuDefinitionKeys !== [] && !in_array($languageKey, $menuDefinitionKeys, true)) {
                        $findings[] = 'Admin page `' . $page . '` installer registration references language key `' . $languageKey . '`, but the menu-definition file does not define it.';
                    }

                    if ($mainPage !== '' && $filenameMatches !== [] && !in_array($mainPage, array_column($filenameMatches, 'constant'), true)) {
                        $findings[] = 'Admin page `' . $page . '` installer registration references `' . $mainPage . '`, but the plugin `filenames.php` does not map that constant to this admin entrypoint.';
                    }
                }
            }
        }

        $observerFiles = $this->listPluginFiles($pluginRoot, [
            'catalog/includes/classes/observers',
            'admin/includes/classes/observers',
        ]);
        $autoloaderFiles = $this->listPluginFiles($pluginRoot, [
            'catalog/includes/auto_loaders',
            'catalog/includes/init_includes',
            'admin/includes/auto_loaders',
            'admin/includes/init_includes',
        ]);
        $extraFiles = $this->listPluginFiles($pluginRoot, [
            'catalog/includes/extra_configures',
            'catalog/includes/extra_datafiles',
            'admin/includes/extra_configures',
            'admin/includes/extra_datafiles',
        ]);
        $installerLanguageFiles = $this->listPluginFiles($pluginRoot, ['Installer/languages']);
        foreach ($installerLanguageFiles as $installerLanguageFile) {
            $analysis = $this->analyzeLanguageFile($this->projectRoot . $installerLanguageFile, 'installer-language');

            if (($analysis['readable'] ?? true) === false) {
                $findings[] = 'Installer language file `' . $installerLanguageFile . '` is unreadable or malformed.';
                continue;
            }

            if (($analysis['has_language_definitions'] ?? true) === false) {
                $findings[] = 'Installer language file `' . $installerLanguageFile . '` does not define any language keys.';
            }
        }
        $skillTopics = $this->listPluginFiles($pluginRoot, ['resources/skills']);
        foreach ($observerFiles as $observerFile) {
            if (!preg_match('#/(?:catalog|admin)/includes/classes/observers/auto_[^/]+\.php$#', $observerFile)) {
                $findings[] = 'Observer file `' . $observerFile . '` does not follow the expected `auto_*.php` naming for encapsulated plugin observers.';
            }
        }

        return [
            'plugin_root' => $pluginRoot,
            'manifest_path' => is_file($pluginRoot . 'manifest.php') ? $this->relativePath($pluginRoot . 'manifest.php') : null,
            'filenames' => $filenames,
            'catalog_pages' => $catalogPages,
            'admin_pages' => $adminPages,
            'installer_language_files' => $installerLanguageFiles,
            'observers' => $observerFiles,
            'autoloaders' => $autoloaderFiles,
            'extra_files' => $extraFiles,
            'skill_topics' => $skillTopics,
            'findings' => $findings,
        ];
    }

    private function filenameDefinitionFiles(): array
    {
        $files = [];
        foreach ([
            'includes/filenames.php',
            'admin/includes/filenames.php',
            'includes/extra_datafiles',
            'admin/includes/extra_datafiles',
            'zc_plugins',
        ] as $relativePath) {
            $absolutePath = $this->projectRoot . $relativePath;
            if (is_file($absolutePath)) {
                $files[] = $absolutePath;
                continue;
            }

            if (!is_dir($absolutePath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absolutePath, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || !preg_match('/filenames\.php$/i', $fileInfo->getFilename())) {
                    continue;
                }

                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    private function normalizePluginRoot(string $path): ?string
    {
        $resolved = realpath($path);
        if ($resolved === false) {
            return null;
        }

        if (is_file($resolved)) {
            $resolved = dirname($resolved);
        }

        $resolved = rtrim($resolved, '/\\') . '/';
        if (basename(rtrim($resolved, '/\\')) === 'Installer') {
            $resolved = dirname(rtrim($resolved, '/\\')) . '/';
        }

        return is_file($resolved . 'manifest.php') ? $resolved : null;
    }

    private function pluginFilenameDefinitions(string $pluginRoot): array
    {
        $path = $pluginRoot . 'filenames.php';
        if (!is_file($path)) {
            return [];
        }

        $contents = @file_get_contents($path);
        if (!is_string($contents) || $contents === '') {
            return [];
        }

        if (!preg_match_all('/define\(\s*[\'"](FILENAME_[A-Z0-9_]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $contents, $rows, PREG_SET_ORDER)) {
            return [];
        }

        $definitions = [];
        foreach ($rows as $row) {
            $definitions[] = [
                'constant' => $row[1],
                'value' => $row[2],
                'path' => $this->relativePath($path),
            ];
        }

        return $definitions;
    }

    private function matchFilenameDefinitions(array $definitions, string $page): array
    {
        $matches = [];
        foreach ($definitions as $definition) {
            $constant = strtolower((string)($definition['constant'] ?? ''));
            $value = strtolower((string)($definition['value'] ?? ''));
            $page = strtolower($page);

            if (str_contains($constant, $page) || str_contains($value, $page)) {
                $matches[] = $definition;
            }
        }

        return $matches;
    }

    private function candidateLogFiles(): array
    {
        $files = [];

        foreach (['logs', 'admin/logs'] as $relativeDirectory) {
            $absoluteDirectory = $this->projectRoot . $relativeDirectory;
            if (!is_dir($absoluteDirectory)) {
                continue;
            }

            foreach (glob($absoluteDirectory . '/*') ?: [] as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $basename = basename($file);
                if (in_array($basename, ['index.html', 'index.php', '.gitignore', '.htaccess'], true)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        usort($files, static function (string $left, string $right): int {
            return (filemtime($right) ?: 0) <=> (filemtime($left) ?: 0);
        });

        return $files;
    }

    private function loadPluginRepositoryContext(): array
    {
        $bootstrap = $this->projectRoot . 'includes/application_cli_bootstrap.php';
        if (!is_file($bootstrap)) {
            return [
                'runtime_state' => 'bootstrap-missing',
                'repository' => null,
                'warnings' => ['CLI bootstrap is unavailable for plugin inspection.'],
            ];
        }

        $capturedWarnings = [];
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_WARNING);

        set_error_handler(static function (int $severity, string $message) use (&$capturedWarnings): bool {
            $capturedWarnings[] = $message;
            return true;
        });

        ob_start();
        try {
            require_once $bootstrap;
            $context = function_exists('zc_cli_get_plugin_repository_context')
                ? zc_cli_get_plugin_repository_context()
                : ['repository' => null, 'warnings' => ['CLI plugin repository helper is unavailable.']];
        } finally {
            $buffer = ob_get_clean();
            restore_error_handler();
            error_reporting($previousErrorReporting);
        }

        if (is_string($buffer) && trim($buffer) !== '') {
            $capturedWarnings[] = trim($buffer);
        }

        $contextWarnings = $context['warnings'] ?? [];
        if (!is_array($contextWarnings)) {
            $contextWarnings = [];
        }

        return [
            'runtime_state' => $this->classifyRuntimeState($context['repository'] ?? null, $contextWarnings, $capturedWarnings),
            'repository' => $context['repository'] ?? null,
            'warnings' => $this->normalizeWarnings(array_merge($contextWarnings, $capturedWarnings)),
        ];
    }

    private function classifyRuntimeState(mixed $repository, array $contextWarnings, array $capturedWarnings): string
    {
        if ($repository !== null) {
            return 'available';
        }

        $warnings = $this->normalizeWarnings(array_merge($contextWarnings, $capturedWarnings));
        $warningText = strtolower(implode(' ', $warnings));

        if ($warningText === '') {
            return 'repository-unavailable';
        }

        if (str_contains($warningText, 'database configuration is unavailable')) {
            return 'db-config-unavailable';
        }

        if (str_contains($warningText, 'mysql connector') || str_contains($warningText, 'php is unavailable')) {
            return 'db-driver-unavailable';
        }

        if (str_contains($warningText, 'unable to connect to the store database')) {
            return 'db-connection-failed';
        }

        if (str_contains($warningText, 'cli plugin repository helper is unavailable')) {
            return 'repository-helper-unavailable';
        }

        if (str_contains($warningText, 'bootstrap is unavailable')) {
            return 'bootstrap-unavailable';
        }

        return 'repository-unavailable';
    }

    private function describeRuntimeState(string $runtimeState): string
    {
        return match ($runtimeState) {
            'available' => 'Installed plugin inspection is available.',
            'invalid-filter' => 'The requested installed-plugin status filter is invalid.',
            'bootstrap-missing' => 'Zen Cart CLI bootstrap is missing, so installed plugin inspection is unavailable.',
            'bootstrap-unavailable' => 'Zen Cart CLI bootstrap could not be used for installed plugin inspection.',
            'db-config-unavailable' => 'Store DB configuration is unavailable, so installed plugin inspection cannot query plugin manager state.',
            'db-driver-unavailable' => 'The PHP MySQL driver is unavailable, so installed plugin inspection cannot query plugin manager state.',
            'db-connection-failed' => 'Store DB connectivity failed, so installed plugin inspection cannot query plugin manager state.',
            'repository-helper-unavailable' => 'The CLI plugin repository helper is unavailable, so installed plugin inspection cannot query plugin manager state.',
            default => 'Installed plugin inspection is unavailable.',
        };
    }

    private function runtimeStateCategory(string $runtimeState): string
    {
        return match ($runtimeState) {
            'available' => 'available',
            'invalid-filter' => 'invalid',
            default => 'degraded',
        };
    }

    private function runtimeStateDetail(string $runtimeState, array $warnings): string
    {
        $warnings = $this->normalizeWarnings($warnings);
        $base = match ($runtimeState) {
            'bootstrap-missing' => 'The CLI bootstrap file is missing.',
            'bootstrap-unavailable' => 'The CLI bootstrap file exists but could not provide plugin repository context.',
            'db-config-unavailable' => 'Store DB configuration is unavailable for CLI plugin inspection.',
            'db-driver-unavailable' => 'The required PHP MySQL driver is unavailable for CLI plugin inspection.',
            'db-connection-failed' => 'The CLI bootstrap could not connect to the store database.',
            'repository-helper-unavailable' => 'The CLI plugin repository helper function is unavailable.',
            'repository-unavailable' => 'Plugin repository context could not be established.',
            'invalid-filter' => 'The requested status filter is invalid.',
            default => 'Installed plugin inspection is unavailable.',
        };

        if ($warnings === []) {
            return $base;
        }

        return $base . ' Warnings: ' . implode(' | ', array_slice($warnings, 0, 3));
    }

    private function runtimeStateRecommendation(string $runtimeState): string
    {
        return match ($runtimeState) {
            'available' => 'Use the runtime-backed plugin manager results as the current store state.',
            'invalid-filter' => 'Use one of: all, enabled, disabled, or not-installed.',
            'bootstrap-missing', 'bootstrap-unavailable' => 'Verify the Zen Cart CLI bootstrap is present and loadable before relying on runtime inspection.',
            'db-config-unavailable' => 'Add working store DB configuration before relying on runtime inspection.',
            'db-driver-unavailable' => 'Enable the PHP MySQL driver required by the Zen Cart CLI runtime.',
            'db-connection-failed' => 'Fix store DB connectivity before relying on runtime inspection.',
            'repository-helper-unavailable' => 'Restore the CLI plugin repository helper used by runtime inspection.',
            default => 'Verify CLI bootstrap and DB connectivity before relying on runtime inspection.',
        };
    }

    private function formatPluginStatus(int $status): string
    {
        return match ($status) {
            1 => 'enabled',
            2 => 'disabled',
            0 => 'not-installed',
            default => 'unknown',
        };
    }

    private function normalizeWarnings(array $warnings): array
    {
        $normalized = [];

        foreach ($warnings as $warning) {
            $warning = trim((string)$warning);
            if ($warning === '') {
                continue;
            }

            if (str_starts_with($warning, 'Constant ') && str_contains($warning, ' already defined')) {
                continue;
            }

            $normalized[] = $warning;
        }

        return array_values(array_unique($normalized));
    }

    private function listFiles(string $relativeDirectory): array
    {
        $absoluteDirectory = $this->projectRoot . $relativeDirectory;
        if (!is_dir($absoluteDirectory)) {
            return [];
        }

        $files = [];
        foreach (glob($absoluteDirectory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                $files[] = $this->relativePath($file);
            }
        }

        sort($files);

        return $files;
    }

    private function listFilesRelativeToPlugin(array $sides, array $targets): array
    {
        $results = [];

        foreach ($targets as $target) {
            if ($target === 'filenames.php') {
                $path = $this->pluginRoot . 'filenames.php';
                if (is_file($path)) {
                    $results[] = $this->relativePath($path);
                }
                continue;
            }

            foreach ($sides as $side) {
                $base = $this->pluginRoot . $side . '/includes/' . $target;
                if (!is_dir($base)) {
                    continue;
                }

                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile()) {
                        $results[] = $this->relativePath($fileInfo->getPathname());
                    }
                }
            }
        }

        sort($results);

        return $results;
    }

    private function listPluginFiles(string $pluginRoot, array $relativeDirectories): array
    {
        $results = [];

        foreach ($relativeDirectories as $relativeDirectory) {
            $absoluteDirectory = $pluginRoot . trim($relativeDirectory, '/');
            if (!is_dir($absoluteDirectory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absoluteDirectory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $results[] = $this->relativePath($fileInfo->getPathname());
            }
        }

        sort($results);

        return array_values(array_unique($results));
    }

    private function templateCandidates(string $page): array
    {
        $candidates = [];
        foreach ([
            'includes/templates/template_default/templates/tpl_' . $page . '_default.php',
            'includes/templates/template_default/templates/tpl_' . $page . '.php',
            'includes/templates',
        ] as $relativePath) {
            $absolutePath = $this->projectRoot . $relativePath;
            if (is_file($absolutePath)) {
                $candidates[] = $relativePath;
                continue;
            }

            if ($relativePath !== 'includes/templates' || !is_dir($absolutePath)) {
                continue;
            }

            foreach (glob($absolutePath . '/*/templates/tpl_' . $page . '*.php') ?: [] as $file) {
                if (is_file($file)) {
                    $candidates[] = $this->relativePath($file);
                }
            }
        }

        sort($candidates);

        return array_values(array_unique($candidates));
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace($this->projectRoot, '', $path), '/');
    }

    private function isReadablePhpIncludeFile(string $path): bool
    {
        $contents = @file_get_contents($path);

        return is_string($contents)
            && trim($contents) !== ''
            && str_contains($contents, '<?php');
    }

    private function menuDefinitionLooksValid(string $path, string $page): bool
    {
        $contents = @file_get_contents($path);
        if (!is_string($contents) || trim($contents) === '') {
            return false;
        }

        $page = strtolower($page);
        $haystack = strtolower($contents);

        if (!str_contains($haystack, '<?php')) {
            return false;
        }

        return str_contains($haystack, 'box_')
            || str_contains($haystack, $page)
            || str_contains($haystack, 'return [');
    }

    private function analyzeLanguageFile(string $path, string $role, string $page = ''): array
    {
        $contents = @file_get_contents($path);
        if (!is_string($contents) || trim($contents) === '' || !str_contains($contents, '<?php')) {
            return [
                'readable' => false,
                'keys' => [],
                'has_language_definitions' => false,
                'has_box_keys' => false,
                'has_expected_page_keys' => false,
            ];
        }

        $keys = $this->languageDefinitionKeys($path);
        $upperKeys = array_map('strtoupper', $keys);
        $pageNeedle = strtoupper(str_replace(['-', ' '], '_', $page));
        $expectedKeys = ['HEADING_TITLE', 'NAVBAR_TITLE', 'TEXT_MAIN', 'TEXT_INFORMATION', 'SUBHEADING_TITLE'];

        $hasExpectedPageKeys = false;
        foreach ($upperKeys as $key) {
            if (in_array($key, $expectedKeys, true)) {
                $hasExpectedPageKeys = true;
                break;
            }

            if ($pageNeedle !== '' && str_contains($key, $pageNeedle)) {
                $hasExpectedPageKeys = true;
                break;
            }
        }

        if ($role === 'installer-language') {
            $hasExpectedPageKeys = true;
        }

        return [
            'readable' => true,
            'keys' => $keys,
            'has_language_definitions' => $keys !== [],
            'has_box_keys' => count(array_filter($upperKeys, static fn (string $key): bool => str_starts_with($key, 'BOX_'))) > 0,
            'has_expected_page_keys' => $hasExpectedPageKeys,
        ];
    }

    private function languageDefinitionKeys(string $path): array
    {
        $contents = @file_get_contents($path);
        if (!is_string($contents) || trim($contents) === '') {
            return [];
        }

        $keys = [];
        if (preg_match_all('/[\'"]([A-Z0-9_]+)[\'"]\s*=>/m', $contents, $matches)) {
            $keys = array_merge($keys, $matches[1]);
        }

        if (preg_match_all('/define\(\s*[\'"]([A-Z0-9_]+)[\'"]/m', $contents, $matches)) {
            $keys = array_merge($keys, $matches[1]);
        }

        $keys = array_values(array_unique(array_filter(array_map(static function (string $key): string {
            return trim($key);
        }, $keys))));
        sort($keys);

        return $keys;
    }

    private function adminPageRegistrations(string $pluginRoot): array
    {
        $registrations = [];
        $installerRoot = $pluginRoot . 'Installer/';
        if (!is_dir($installerRoot)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($installerRoot, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || !preg_match('/\.php$/i', $fileInfo->getFilename())) {
                continue;
            }

            $contents = @file_get_contents($fileInfo->getPathname());
            if (!is_string($contents) || $contents === '') {
                continue;
            }

            if (!preg_match_all(
                '/zen_register_admin_page\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*([^)]+?))?\s*\)/',
                $contents,
                $matches,
                PREG_SET_ORDER
            )) {
                continue;
            }

            foreach ($matches as $match) {
                $registrations[] = [
                    'path' => $this->relativePath($fileInfo->getPathname()),
                    'page_key' => trim($match[1]),
                    'language_key' => trim($match[2]),
                    'main_page' => trim($match[3]),
                    'page_params' => trim($match[4]),
                    'menu_key' => trim($match[5]),
                    'display_on_menu' => trim($match[6]),
                    'sort_order' => isset($match[7]) ? trim($match[7]) : '',
                ];
            }
        }

        return $registrations;
    }

    private function matchAdminPageRegistrations(array $registrations, array $filenameMatches, array $menuDefinitionKeys, string $page): array
    {
        if ($registrations === []) {
            return [];
        }

        $filenameConstants = array_values(array_filter(array_map(static function (array $match): string {
            return trim((string)($match['constant'] ?? ''));
        }, $filenameMatches)));
        $menuDefinitionKeys = array_map('strtoupper', $menuDefinitionKeys);
        $page = strtolower($page);

        $matches = [];
        foreach ($registrations as $registration) {
            $mainPage = trim((string)($registration['main_page'] ?? ''));
            $languageKey = strtoupper(trim((string)($registration['language_key'] ?? '')));
            $pageKey = strtolower(trim((string)($registration['page_key'] ?? '')));

            if ($mainPage !== '' && in_array($mainPage, $filenameConstants, true)) {
                $matches[] = $registration;
                continue;
            }

            if ($languageKey !== '' && in_array($languageKey, $menuDefinitionKeys, true)) {
                $matches[] = $registration;
                continue;
            }

            if ($pageKey !== '' && str_contains($pageKey, str_replace('_', '', $page))) {
                $matches[] = $registration;
            }
        }

        return $matches;
    }
}
