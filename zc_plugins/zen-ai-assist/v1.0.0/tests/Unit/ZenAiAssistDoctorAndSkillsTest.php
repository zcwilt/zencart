<?php

namespace Tests\PluginLocal\ZenAiAssist\Unit;

use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use Tests\Support\Traits\PluginLocalTestConcerns;
use Tests\Support\zcUnitTestCase;

#[Group('parallel-candidate')]
class ZenAiAssistDoctorAndSkillsTest extends zcUnitTestCase
{
    use PluginLocalTestConcerns;

    public function setUp(): void
    {
        parent::setUp();
        $this->bootPluginLocalTest(__FILE__);
    }

    public function testDoctorUsesDeeperStructureChecksAndSkillServiceReadsTopics(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeFile($pluginRoot . 'manifest.php', "<?php\nreturn ['pluginVersion' => 'v1.0.0', 'pluginName' => 'Example', 'pluginDescription' => 'Example plugin', 'pluginAuthor' => 'Tester', 'pluginId' => 0, 'zcVersions' => []];\n");
            $this->writeFile($pluginRoot . 'filenames.php', "<?php\ndefine('FILENAME_EXAMPLE', 'example');\n");
            $this->writeFile($pluginRoot . 'Installer/ScriptedInstaller.php', "<?php\nclass ScriptedInstaller { public function validateInstall() {} public function executeInstall() { zen_register_admin_page('toolsExample', 'BOX_TOOLS_EXAMPLE', 'FILENAME_EXAMPLE', '', 'tools', 'Y', 20); } public function executeUninstall() {} }\n");
            $this->writeFile($pluginRoot . 'Installer/languages/english/main.php', "<?php\ndefine('ERROR_EXAMPLE_INSTALL', 'Example install error.');\n");
            $this->writeFile($pluginRoot . 'catalog/includes/modules/pages/example/header_php.php', "<?php\n");
            $this->writeFile($pluginRoot . 'catalog/includes/languages/english/lang.example.php', "<?php\nreturn ['HEADING_TITLE' => 'Example', 'TEXT_MAIN' => 'Example'];\n");
            $this->writeFile($pluginRoot . 'catalog/includes/templates/template_default/tpl_example.php', "<?php\n");
            $this->writeFile($pluginRoot . 'admin/example.php', "<?php\n");
            $this->writeFile($pluginRoot . 'admin/includes/languages/english/lang.example.php', "<?php\nreturn ['HEADING_TITLE' => 'Example'];\n");
            $this->writeFile($pluginRoot . 'admin/includes/languages/english/extra_definitions/lang.example_menu.php', "<?php\nreturn ['BOX_TOOLS_EXAMPLE' => 'Example'];\n");
            $this->writeFile($pluginRoot . 'catalog/includes/classes/observers/auto_ExampleObserver.php', "<?php\nclass auto_ExampleObserver extends base { public function __construct() { \$this->attach(\$this, ['NOTIFY_HEADER_START_INDEX']); } public function update(&\$class, \$eventID, \$paramsArray = []) {} }\n");
            $this->writeFile($pluginRoot . 'resources/skills/plugin-workflow.md', "# Example Plugin Workflow\n\nChecklist\n");

            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);
            $structure = $inspector->inspectPluginStructure($pluginRoot);
            $this->assertSame([], $structure['findings']);
            $this->assertNotEmpty($structure['skill_topics']);

            $doctorInspector = new class($projectRoot, $pluginRoot) extends \ZenAiAssistRuntimeInspector {
                public function listInstalledPlugins(string $statusFilter = 'all'): array
                {
                    return [
                        'runtime_state' => 'available',
                        'status_filter' => $statusFilter,
                        'warnings' => [],
                        'plugins' => [[
                            'unique_key' => 'example',
                            'name' => 'Example',
                            'version' => 'v1.0.0',
                            'status' => 'enabled',
                            'author' => 'Tester',
                            'description' => 'Example plugin',
                            'zc_versions' => '',
                            'manifest_path' => 'zc_plugins/example/v1.0.0/manifest.php',
                        ]],
                    ];
                }
            };
            $doctor = new \ZenAiAssistDoctorService($projectRoot, null, null, $doctorInspector);
            $result = $doctor->diagnose($pluginRoot);
            $this->assertTrue($result['ok']);
            $this->assertSame(0, $result['issue_counts']['error']);

            $skills = new \ZenAiAssistSkillService($pluginRoot . 'resources/skills/');
            $topics = $skills->listTopics();
            $this->assertCount(1, $topics);
            $topic = $skills->readTopic('plugin-workflow');
            $this->assertTrue($topic['found']);
            $this->assertStringContainsString('Example Plugin Workflow', $topic['content']);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testInspectPluginStructureFlagsAdminMenuRegistrationMismatch(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeFile($pluginRoot . 'manifest.php', "<?php\nreturn ['pluginVersion' => 'v1.0.0', 'pluginName' => 'Example', 'pluginDescription' => 'Example plugin', 'pluginAuthor' => 'Tester', 'pluginId' => 0, 'zcVersions' => []];\n");
            $this->writeFile($pluginRoot . 'filenames.php', "<?php\ndefine('FILENAME_EXAMPLE', 'example');\n");
            $this->writeFile($pluginRoot . 'Installer/ScriptedInstaller.php', "<?php\nclass ScriptedInstaller { public function executeInstall() { zen_register_admin_page('toolsExample', 'BOX_TOOLS_WRONG', 'FILENAME_WRONG', '', 'tools', 'Y', 20); } }\n");
            $this->writeFile($pluginRoot . 'Installer/languages/english/main.php', "<?php\nreturn [];\n");
            $this->writeFile($pluginRoot . 'admin/example.php', "<?php\n");
            $this->writeFile($pluginRoot . 'admin/includes/languages/english/lang.example.php', "<?php\nreturn ['HEADING_TITLE' => 'Example'];\n");
            $this->writeFile($pluginRoot . 'admin/includes/languages/english/extra_definitions/lang.example_menu.php', "<?php\nreturn ['BOX_TOOLS_EXAMPLE' => 'Example'];\n");

            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);
            $structure = $inspector->inspectPluginStructure($pluginRoot);

            $this->assertContains(
                'Admin page `example` installer registration references language key `BOX_TOOLS_WRONG`, but the menu-definition file does not define it.',
                $structure['findings']
            );
            $this->assertContains(
                'Admin page `example` installer registration references `FILENAME_WRONG`, but the plugin `filenames.php` does not map that constant to this admin entrypoint.',
                $structure['findings']
            );
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testListInstalledPluginsClassifiesBootstrapAndDbConfigFailures(): void
    {
        $bootstrapMissingRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $bootstrapPresentRoot = $this->makeTempDirectory('zen-ai-assist-project');

        try {
            $missingBootstrapInspector = new \ZenAiAssistRuntimeInspector($bootstrapMissingRoot, $bootstrapMissingRoot . 'zc_plugins/example/v1.0.0/');
            $missingBootstrapResult = $missingBootstrapInspector->listInstalledPlugins('all');
            $this->assertSame('bootstrap-missing', $missingBootstrapResult['runtime_state']);
            $this->assertSame('degraded', $missingBootstrapResult['runtime_state_category']);
            $this->assertFalse($missingBootstrapResult['inspection_available']);
            $this->assertStringContainsString('bootstrap is missing', strtolower($missingBootstrapResult['runtime_state_message']));
            $this->assertStringContainsString('bootstrap file is missing', strtolower($missingBootstrapResult['runtime_state_detail']));

            $dbConfigInspector = new class($bootstrapPresentRoot, $bootstrapPresentRoot . 'zc_plugins/example/v1.0.0/') extends \ZenAiAssistRuntimeInspector {
                public function listInstalledPlugins(string $statusFilter = 'all'): array
                {
                    return [
                        'runtime_state' => 'db-config-unavailable',
                        'inspection_available' => false,
                        'runtime_state_message' => 'Store DB configuration is unavailable, so installed plugin inspection cannot query plugin manager state.',
                        'status_filter' => $statusFilter,
                        'warnings' => ['Plugin command discovery disabled: store database configuration is unavailable.'],
                        'plugins' => [],
                    ];
                }
            };
            $dbConfigResult = $dbConfigInspector->listInstalledPlugins('all');

            $this->assertSame('db-config-unavailable', $dbConfigResult['runtime_state']);
            $this->assertSame([], $dbConfigResult['plugins']);
        } finally {
            $this->removeDirectory(rtrim($bootstrapMissingRoot, '/\\'));
            $this->removeDirectory(rtrim($bootstrapPresentRoot, '/\\'));
        }
    }

    public function testRuntimeStateClassifierRecognizesAdditionalFailureModes(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);
            $method = new ReflectionMethod(\ZenAiAssistRuntimeInspector::class, 'classifyRuntimeState');
            $method->setAccessible(true);

            $this->assertSame('db-driver-unavailable', $method->invoke($inspector, null, ['Plugin command discovery disabled: the MySQL connector for PHP is unavailable.'], []));
            $this->assertSame('db-connection-failed', $method->invoke($inspector, null, ['Plugin command discovery disabled: unable to connect to the store database.'], []));
            $this->assertSame('repository-helper-unavailable', $method->invoke($inspector, null, ['CLI plugin repository helper is unavailable.'], []));
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testListInstalledPluginsExposesNormalizedRuntimeContext(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);

            $invalidFilter = $inspector->listInstalledPlugins('bogus');
            $this->assertSame('invalid-filter', $invalidFilter['runtime_context']['state']);
            $this->assertSame('invalid', $invalidFilter['runtime_context']['category']);
            $this->assertFalse($invalidFilter['runtime_context']['inspection_available']);
            $this->assertSame($invalidFilter['runtime_state_detail'], $invalidFilter['runtime_context']['detail']);
            $this->assertNotSame('', $invalidFilter['runtime_context']['recommended_action']);

            $bootstrapMissing = $inspector->listInstalledPlugins('all');
            $this->assertSame('bootstrap-missing', $bootstrapMissing['runtime_context']['state']);
            $this->assertSame('degraded', $bootstrapMissing['runtime_context']['category']);
            $this->assertFalse($bootstrapMissing['runtime_context']['inspection_available']);
            $this->assertSame($bootstrapMissing['runtime_state_message'], $bootstrapMissing['runtime_context']['message']);
            $this->assertNotSame('', $bootstrapMissing['runtime_context']['recommended_action']);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testPluginDoctorCommandPrintsInstalledAndRuntimeState(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $checkoutRoot = dirname(__DIR__, 5);
            require_once $checkoutRoot . '/includes/classes/Console/ConsoleCommand.php';
            require_once $checkoutRoot . '/includes/classes/Console/ConsoleInput.php';
            require_once $checkoutRoot . '/includes/classes/Console/ConsoleOutput.php';
            require_once dirname(__DIR__, 2) . '/Console/Commands/AbstractZenAiAssistCommand.php';
            require_once dirname(__DIR__, 2) . '/Console/Commands/PluginDoctorCommand.php';
            $this->writeExamplePlugin($pluginRoot);

            $command = new class($projectRoot, $pluginRoot) extends \Zencart\Plugins\Console\ZenAiAssist\Commands\PluginDoctorCommand {
                public function __construct(private string $testProjectRoot, private string $testPluginRoot)
                {
                }

                protected function projectRoot(): string
                {
                    return rtrim($this->testProjectRoot, '/\\') . '/';
                }

                protected function pluginRoot(): string
                {
                    return rtrim($this->testPluginRoot, '/\\') . '/';
                }
            };

            $stdout = fopen('php://temp', 'w+');
            $stderr = fopen('php://temp', 'w+');
            $exitCode = $command->handle(
                new \Zencart\Console\ConsoleInput(['zc_cli.php', 'ai:plugin:doctor', $pluginRoot]),
                new \Zencart\Console\ConsoleOutput($stdout, $stderr)
            );

            rewind($stdout);
            $output = stream_get_contents($stdout);
            for ($i = 0; $i < 3; $i++) {
                if (!restore_error_handler()) {
                    break;
                }
            }

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString('Installed state: ', $output);
            $this->assertStringContainsString('Runtime state: ', $output);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testPluginDoctorCommandCanScanAllPlugins(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $firstPluginRoot = $projectRoot . 'zc_plugins/example-a/v1.0.0/';
        $secondPluginRoot = $projectRoot . 'zc_plugins/example-b/v2.0.0/';

        try {
            require_once dirname(__DIR__, 5) . '/includes/classes/Console/ConsoleCommand.php';
            require_once dirname(__DIR__, 5) . '/includes/classes/Console/ConsoleInput.php';
            require_once dirname(__DIR__, 5) . '/includes/classes/Console/ConsoleOutput.php';
            require_once dirname(__DIR__, 2) . '/Console/Commands/AbstractZenAiAssistCommand.php';
            require_once dirname(__DIR__, 2) . '/Console/Commands/PluginDoctorCommand.php';

            $this->writeFile($firstPluginRoot . 'manifest.php', "<?php\nreturn [];\n");
            $this->writeFile($secondPluginRoot . 'manifest.php', "<?php\nreturn [];\n");

            $command = new class($projectRoot) extends \Zencart\Plugins\Console\ZenAiAssist\Commands\PluginDoctorCommand {
                public function __construct(private string $testProjectRoot)
                {
                }

                protected function projectRoot(): string
                {
                    return rtrim($this->testProjectRoot, '/\\') . '/';
                }

                protected function createDoctor(): \ZenAiAssistDoctorService
                {
                    return new class($this->projectRoot()) extends \ZenAiAssistDoctorService {
                        public function __construct(private string $testProjectRoot)
                        {
                        }

                        public function diagnose(string $path): array
                        {
                            $pluginKey = basename(dirname(rtrim($path, '/\\')));
                            $ok = $pluginKey === 'example-a';

                            return [
                                'ok' => $ok,
                                'message' => $ok ? 'Plugin passed the current Zen AI Assist doctor checks.' : 'Plugin has one or more issues to address.',
                                'plugin_key' => $pluginKey,
                                'plugin_version' => basename(rtrim($path, '/\\')),
                                'issue_counts' => [
                                    'error' => $ok ? 0 : 1,
                                    'warning' => 0,
                                    'info' => 0,
                                ],
                                'checks' => [
                                    'installed_state' => [
                                        'status' => 'found',
                                        'runtime_state' => 'available',
                                    ],
                                ],
                                'issues' => $ok ? [] : [[
                                    'severity' => 'error',
                                    'message' => 'Synthetic failure.',
                                ]],
                                'findings' => $ok ? [] : ['Synthetic failure.'],
                                'recommendations' => $ok ? ['Looks good.'] : ['Needs attention.'],
                            ];
                        }
                    };
                }
            };

            $stdout = fopen('php://temp', 'w+');
            $stderr = fopen('php://temp', 'w+');
            $exitCode = $command->handle(
                new \Zencart\Console\ConsoleInput(['zc_cli.php', 'ai:plugin:doctor', '--all']),
                new \Zencart\Console\ConsoleOutput($stdout, $stderr)
            );

            rewind($stdout);
            $output = stream_get_contents($stdout);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Scanning all plugin roots under: ', $output);
            $this->assertStringContainsString('Using plugin path: ' . $firstPluginRoot, $output);
            $this->assertStringContainsString('Using plugin path: ' . $secondPluginRoot, $output);
            $this->assertStringContainsString('Plugin: example-a', $output);
            $this->assertStringContainsString('Plugin: example-b', $output);
            $this->assertStringContainsString('ERROR: Synthetic failure.', $output);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testDoctorDistinguishesMissingPluginFromRuntimeUnavailable(): void
    {
        $runtimeUnavailableRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $missingPluginRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $runtimeUnavailableRoot . 'zc_plugins/example/v1.0.0/';
        $missingPluginPath = $missingPluginRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeExamplePlugin($pluginRoot);
            $runtimeUnavailableInspector = new class($runtimeUnavailableRoot, $pluginRoot) extends \ZenAiAssistRuntimeInspector {
                public function listInstalledPlugins(string $statusFilter = 'all'): array
                {
                    return [
                        'runtime_state' => 'db-config-unavailable',
                        'status_filter' => $statusFilter,
                        'warnings' => ['Plugin command discovery disabled: store database configuration is unavailable.'],
                        'plugins' => [],
                    ];
                }
            };
            $runtimeUnavailableDoctor = new \ZenAiAssistDoctorService($runtimeUnavailableRoot, null, null, $runtimeUnavailableInspector);
            $runtimeUnavailableResult = $runtimeUnavailableDoctor->diagnose($pluginRoot);

            $this->assertSame('runtime-unavailable', $runtimeUnavailableResult['checks']['installed_state']['status']);
            $this->assertSame('db-config-unavailable', $runtimeUnavailableResult['checks']['installed_state']['runtime_state']);
            $this->assertContains('Installed plugin state could not be inspected because the CLI runtime context is unavailable.', $runtimeUnavailableResult['findings']);
            $this->assertSame(0, $runtimeUnavailableResult['issue_counts']['error']);
            $this->assertNotContains('Plugin is not present in plugin manager state.', $runtimeUnavailableResult['findings']);

            $this->writeExamplePlugin($missingPluginPath);
            $missingPluginInspector = new class($missingPluginRoot, $missingPluginPath) extends \ZenAiAssistRuntimeInspector {
                public function listInstalledPlugins(string $statusFilter = 'all'): array
                {
                    return [
                        'runtime_state' => 'available',
                        'status_filter' => $statusFilter,
                        'warnings' => [],
                        'plugins' => [],
                    ];
                }
            };
            $missingPluginDoctor = new \ZenAiAssistDoctorService($missingPluginRoot, null, null, $missingPluginInspector);
            $missingPluginResult = $missingPluginDoctor->diagnose($missingPluginPath);

            $this->assertSame('missing', $missingPluginResult['checks']['installed_state']['status']);
            $this->assertContains('Plugin is not present in plugin manager state.', $missingPluginResult['findings']);
        } finally {
            $this->removeDirectory(rtrim($runtimeUnavailableRoot, '/\\'));
            $this->removeDirectory(rtrim($missingPluginRoot, '/\\'));
        }
    }

    public function testStructuredSkillsCanBeListedMatchedAndValidated(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeExamplePlugin($pluginRoot);
            $this->writeFile(
                $pluginRoot . 'resources/skills/catalog.json',
                <<<'JSON'
{
  "skills": [
    {
      "id": "plugin-workflow",
      "title": "Example Plugin Workflow",
      "summary": "Guide work on an encapsulated Zen Cart plugin.",
      "intent": "Keep plugin work inside the expected manifest and installer structure.",
      "tags": ["plugin", "workflow"],
      "when_to_use": ["Creating or fixing a plugin."],
      "workflow_steps": ["Inspect manifest.php and Installer/ first."],
      "validation_rules": [
        {
          "type": "path_exists",
          "root": "plugin",
          "path": "manifest.php",
          "description": "Plugin manifest exists."
        },
        {
          "type": "path_exists",
          "root": "plugin",
          "path": "Installer/ScriptedInstaller.php",
          "description": "Plugin installer exists."
        }
      ],
      "content_file": "plugin-workflow.md"
    }
  ]
}
JSON
            );

            $skills = new \ZenAiAssistSkillService($pluginRoot . 'resources/skills/');

            $listed = $skills->listSkills();
            $this->assertCount(1, $listed);
            $this->assertSame('plugin-workflow', $listed[0]['id']);

            $loaded = $skills->getSkill('plugin-workflow');
            $this->assertTrue($loaded['found']);
            $this->assertSame('Example Plugin Workflow', $loaded['title']);
            $this->assertStringContainsString('Example Plugin Workflow', $loaded['content']);

            $matches = $skills->matchSkill('create or fix a plugin', 1);
            $this->assertCount(1, $matches['matches']);
            $this->assertSame('plugin-workflow', $matches['matches'][0]['id']);

            $validation = $skills->validateSkill('plugin-workflow', ['plugin_root' => $pluginRoot]);
            $this->assertTrue($validation['ok']);
            $this->assertSame(2, $validation['passed']);
            $this->assertSame(0, $validation['failed']);
            $this->assertSame(0, $validation['skipped']);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testDoctorFlagsSemanticEncapsulatedPluginProblems(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeExamplePlugin($pluginRoot);
            unlink($pluginRoot . 'catalog/includes/classes/observers/auto_ExampleObserver.php');
            $this->writeFile($pluginRoot . 'catalog/includes/classes/observers/ExampleObserver.php', "<?php\nclass ExampleObserver {}\n");
            $this->writeFile($pluginRoot . 'admin/includes/languages/english/extra_definitions/lang.example_menu.php', '');
            $this->writeFile($pluginRoot . 'admin/includes/languages/english/lang.example.php', "<?php\nreturn [];\n");
            $this->writeFile($pluginRoot . 'catalog/includes/languages/english/lang.example.php', '');
            $this->writeFile($pluginRoot . 'Installer/languages/english/main.php', "<?php\nreturn [];\n");

            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);
            $structure = $inspector->inspectPluginStructure($pluginRoot);

            $this->assertContains(
                'Catalog page `example` has an unreadable or malformed language file.',
                $structure['findings']
            );
            $this->assertContains(
                'Admin page `example` has a menu-definition file that does not appear to define an encapsulated admin menu label.',
                $structure['findings']
            );
            $this->assertContains(
                'Admin page `example` language file does not define any language keys.',
                $structure['findings']
            );
            $this->assertContains(
                'Admin page `example` language file does not define any typical admin page-language keys.',
                $structure['findings']
            );
            $this->assertContains(
                'Installer language file `zc_plugins/example/v1.0.0/Installer/languages/english/main.php` does not define any language keys.',
                $structure['findings']
            );
            $this->assertContains(
                'Observer file `zc_plugins/example/v1.0.0/catalog/includes/classes/observers/ExampleObserver.php` does not follow the expected encapsulated plugin observer naming patterns.',
                $structure['findings']
            );
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testDoctorFlagsObserverAndLoaderRuntimeIntentProblems(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeExamplePlugin($pluginRoot);
            $this->writeFile($pluginRoot . 'catalog/includes/classes/observers/auto_ExampleObserver.php', "<?php\nclass ExampleObserver {}\n");
            $this->writeFile($pluginRoot . 'catalog/includes/auto_loaders/config.example.php', "<?php\n\$autoLoadConfig[80][] = ['autoType' => 'init_script', 'loadFile' => 'missing_example.php'];\n");
            $this->writeFile($pluginRoot . 'catalog/includes/init_includes/init_example.php', "<?php\n\$flag = true;\n");

            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);
            $structure = $inspector->inspectPluginStructure($pluginRoot);

            $this->assertContains(
                'Observer file `zc_plugins/example/v1.0.0/catalog/includes/classes/observers/auto_ExampleObserver.php` does not declare a class extending `base`.',
                $structure['findings']
            );
            $this->assertContains(
                'Observer file `zc_plugins/example/v1.0.0/catalog/includes/classes/observers/auto_ExampleObserver.php` does not appear to attach to notifications or expose update handlers.',
                $structure['findings']
            );
            $this->assertContains(
                'Loader file `zc_plugins/example/v1.0.0/catalog/includes/auto_loaders/config.example.php` references missing file `zc_plugins/example/v1.0.0/catalog/includes/init_includes/missing_example.php`.',
                $structure['findings']
            );
            $this->assertContains(
                'Loader file `zc_plugins/example/v1.0.0/catalog/includes/init_includes/init_example.php` does not guard direct access with `IS_ADMIN_FLAG`.',
                $structure['findings']
            );
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testDoctorDoesNotFailConsoleFocusedPluginWithoutBootstrapHooks(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeFile($pluginRoot . 'manifest.php', "<?php\nreturn ['pluginVersion' => 'v1.0.0', 'pluginName' => 'Example', 'pluginDescription' => 'Example plugin', 'pluginAuthor' => 'Tester', 'pluginId' => 0, 'zcVersions' => []];\n");
            $this->writeFile($pluginRoot . 'Installer/ScriptedInstaller.php', "<?php\nclass ScriptedInstaller { public function validateInstall() {} public function executeInstall() {} public function executeUninstall() {} }\n");
            $this->writeFile($pluginRoot . 'Installer/languages/english/main.php', "<?php\nreturn [];\n");
            $this->writeFile($pluginRoot . 'Console/commands.php', "<?php\nreturn [];\n");
            $this->writeFile($pluginRoot . 'resources/skills/plugin-workflow.md', "# Example Plugin Workflow\n\nChecklist\n");

            $doctorInspector = new class($projectRoot, $pluginRoot) extends \ZenAiAssistRuntimeInspector {
                public function listInstalledPlugins(string $statusFilter = 'all'): array
                {
                    return [
                        'runtime_state' => 'available',
                        'status_filter' => $statusFilter,
                        'warnings' => [],
                        'plugins' => [[
                            'unique_key' => 'example',
                            'name' => 'Example',
                            'version' => 'v1.0.0',
                            'status' => 'enabled',
                            'author' => 'Tester',
                            'description' => 'Example plugin',
                            'zc_versions' => '',
                            'manifest_path' => 'zc_plugins/example/v1.0.0/manifest.php',
                        ]],
                    ];
                }
            };

            $doctor = new \ZenAiAssistDoctorService($projectRoot, null, null, $doctorInspector);
            $result = $doctor->diagnose($pluginRoot);

            $this->assertTrue($result['ok']);
            $this->assertSame(0, $result['issue_counts']['error']);
            $this->assertNotContains(
                'Plugin does not currently expose observers, autoloaders, or extra configure/data files.',
                $result['findings']
            );
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testAnswerServiceCanComposeSkillContextWithDocsAndRepoEvidence(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeExamplePlugin($pluginRoot);
            $this->writeFile(
                $pluginRoot . 'resources/skills/catalog.json',
                <<<'JSON'
{
  "skills": [
                    {
                      "id": "wire-language-files",
                      "title": "Wire Language Files",
                      "summary": "Add the expected Zen Cart language files.",
                      "intent": "Guide language-file wiring for pages and admin pages.",
                      "tags": ["language", "plugin", "admin", "storefront"],
                      "when_to_use": ["A page or admin page needs language definitions."],
                      "workflow_steps": ["Place admin page strings under admin/includes/languages/english/."],
                      "validation_rules": [
                        {
                          "type": "path_exists",
                          "root": "plugin",
                          "path": "manifest.php",
                          "description": "Plugin manifest exists."
                        }
                      ],
                      "content_file": "wire-language-files.md"
                    }
                  ]
                }
JSON
            );
            $this->writeFile($pluginRoot . 'resources/skills/wire-language-files.md', "# Wire Language Files\n\nUse the expected Zen Cart language paths.\n");

            $skills = new \ZenAiAssistSkillService($pluginRoot . 'resources/skills/');
            $doctor = new class extends \ZenAiAssistDoctorService {
                public function __construct()
                {
                }

                public function diagnose(string $path): array
                {
                    return [
                        'ok' => true,
                        'message' => 'Plugin passed the current Zen AI Assist doctor checks.',
                        'plugin_root' => $path,
                        'runtime_context' => [
                            'state' => 'available',
                            'category' => 'available',
                            'inspection_available' => true,
                            'message' => 'Installed plugin inspection is available.',
                            'detail' => 'Installed plugin inspection resolved plugin manager state successfully.',
                            'warnings' => [],
                            'recommended_action' => 'Use the runtime-backed plugin manager results as the current store state.',
                        ],
                        'issue_counts' => [
                            'error' => 0,
                            'warning' => 0,
                            'info' => 0,
                        ],
                        'checks' => [
                            'installed_state' => [
                                'status' => 'found',
                                'runtime_state' => 'available',
                                'runtime_context' => [
                                    'state' => 'available',
                                    'category' => 'available',
                                    'inspection_available' => true,
                                    'message' => 'Installed plugin inspection is available.',
                                    'detail' => 'Installed plugin inspection resolved plugin manager state successfully.',
                                    'warnings' => [],
                                    'recommended_action' => 'Use the runtime-backed plugin manager results as the current store state.',
                                ],
                            ],
                        ],
                        'issues' => [],
                        'findings' => [],
                        'recommendations' => [],
                    ];
                }
            };
            $service = new \ZenAiAssistAnswerService(new \ZenAiAssistComparisonService(), $skills, $doctor);

            $docsIndex = [
                'chunks' => [[
                    'title' => 'Admin language docs',
                    'heading_path' => ['Plugins', 'Admin'],
                    'excerpt' => 'Admin pages should load matching language definitions.',
                    'url' => 'https://docs.example.test/admin-language',
                    'content' => 'Admin pages should load matching language definitions.',
                    'tags' => ['admin', 'language'],
                ]],
            ];
            $repoIndex = [
                'records' => [[
                    'title' => 'Example admin language file',
                    'path' => 'zc_plugins/example/v1.0.0/admin/includes/languages/english/lang.example.php',
                    'excerpt' => 'Language definitions for the Example admin page.',
                    'content' => 'Language definitions for the Example admin page.',
                    'path_tokens' => ['admin', 'languages', 'english'],
                ]],
            ];

            $answer = $service->answerWithSkillContext($docsIndex, $repoIndex, 'wire admin language files', 2, 2, $pluginRoot);

            $this->assertSame('wire-language-files', $answer['recommended_skill']['id']);
            $this->assertTrue($answer['recommended_skill_detail']['found']);
            $this->assertStringContainsString('Wire Language Files', $answer['workflow_hint']);
            $this->assertNotEmpty($answer['docs']);
            $this->assertNotEmpty($answer['repo']);
            $this->assertContains('admin', $answer['query_type']['categories']);
            $this->assertSame('attached', $answer['plugin_context']['status']);
            $this->assertSame('found', $answer['plugin_context']['installed_state']['status']);
            $this->assertSame('available', $answer['plugin_context']['runtime_context']['state']);
            $this->assertTrue($answer['plugin_context']['runtime_context']['inspection_available']);
            $this->assertTrue($answer['plugin_context']['doctor']['ok']);
            $this->assertNotEmpty($answer['recommended_next_steps']);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testDoctorFlagsCatalogPageTemplateWiringProblems(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $pluginRoot = $projectRoot . 'zc_plugins/example/v1.0.0/';

        try {
            $this->writeExamplePlugin($pluginRoot);
            $this->writeFile(
                $pluginRoot . 'catalog/includes/modules/pages/example/main_template_vars.php',
                "<?php\n\$tpl_page_body = 'tpl_example_default.php';\n"
            );

            $inspector = new \ZenAiAssistRuntimeInspector($projectRoot, $pluginRoot);
            $structure = $inspector->inspectPluginStructure($pluginRoot);

            $this->assertContains(
                'Catalog page `example` `main_template_vars.php` references missing template `tpl_example_default.php`.',
                $structure['findings']
            );
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    public function testContentRegistryAggregatesCoreInstalledPluginAndBundledContent(): void
    {
        $projectRoot = $this->makeTempDirectory('zen-ai-assist-project');
        $bundledPluginRoot = $projectRoot . 'zc_plugins/zen-ai-assist/v1.0.0/';
        $externalPluginRoot = $projectRoot . 'zc_plugins/debug-bar/v1.0.5/';

        try {
            $this->writeFile($projectRoot . 'includes/zen_ai_assist/guidance/core-pages.md', "# Core Pages\n\nCore guidance.\n");
            $this->writeFile(
                $projectRoot . 'includes/zen_ai_assist/skills/catalog.json',
                <<<'JSON'
{
  "skills": [
    {
      "id": "shared-skill",
      "title": "Core Shared Skill",
      "summary": "Core wins duplicate ids.",
      "content_file": "shared-skill.md"
    }
  ]
}
JSON
            );
            $this->writeFile($projectRoot . 'includes/zen_ai_assist/skills/shared-skill.md', "# Core Shared Skill\n\nCore content.\n");

            $this->writeFile($bundledPluginRoot . 'resources/guidance/bundled-topic.md', "# Bundled Topic\n\nBundled guidance.\n");
            $this->writeFile(
                $bundledPluginRoot . 'resources/skills/catalog.json',
                <<<'JSON'
{
  "skills": [
    {
      "id": "shared-skill",
      "title": "Bundled Shared Skill",
      "summary": "Bundled duplicate should lose.",
      "content_file": "shared-skill.md"
    },
    {
      "id": "bundled-only",
      "title": "Bundled Only Skill",
      "summary": "Bundled unique skill.",
      "content_file": "bundled-only.md"
    }
  ]
}
JSON
            );
            $this->writeFile($bundledPluginRoot . 'resources/skills/shared-skill.md', "# Bundled Shared Skill\n\nBundled content.\n");
            $this->writeFile($bundledPluginRoot . 'resources/skills/bundled-only.md', "# Bundled Only Skill\n\nBundled unique content.\n");

            $this->writeFile($externalPluginRoot . 'manifest.php', "<?php\nreturn ['pluginVersion' => 'v1.0.5', 'pluginName' => 'Debug Bar'];\n");
            $this->writeFile($externalPluginRoot . 'resources/zen_ai_assist/guidance/plugin-runtime.md', "# Plugin Runtime\n\nPlugin guidance.\n");
            $this->writeFile(
                $externalPluginRoot . 'resources/zen_ai_assist/skills/catalog.json',
                <<<'JSON'
{
  "skills": [
    {
      "id": "shared-skill",
      "title": "Plugin Shared Skill",
      "summary": "Plugin duplicate should lose to core.",
      "content_file": "shared-skill.md"
    },
    {
      "id": "plugin-only",
      "title": "Plugin Only Skill",
      "summary": "Plugin unique skill.",
      "content_file": "plugin-only.md"
    }
  ]
}
JSON
            );
            $this->writeFile($externalPluginRoot . 'resources/zen_ai_assist/skills/shared-skill.md', "# Plugin Shared Skill\n\nPlugin content.\n");
            $this->writeFile($externalPluginRoot . 'resources/zen_ai_assist/skills/plugin-only.md', "# Plugin Only Skill\n\nPlugin unique content.\n");

            $runtimeInspector = new class($projectRoot, $bundledPluginRoot) extends \ZenAiAssistRuntimeInspector {
                public function listInstalledPlugins(string $statusFilter = 'all'): array
                {
                    return [
                        'runtime_state' => 'available',
                        'runtime_context' => [
                            'state' => 'available',
                            'category' => 'available',
                            'inspection_available' => true,
                            'message' => 'Installed plugin inspection is available.',
                            'detail' => 'Installed plugin inspection resolved plugin manager state successfully.',
                            'warnings' => [],
                            'recommended_action' => 'Use the runtime-backed plugin manager results as the current store state.',
                        ],
                        'status_filter' => $statusFilter,
                        'warnings' => [],
                        'plugins' => [[
                            'unique_key' => 'debug-bar',
                            'name' => 'Debug Bar',
                            'version' => 'v1.0.5',
                            'status' => 'enabled',
                            'manifest_path' => 'zc_plugins/debug-bar/v1.0.5/manifest.php',
                        ]],
                    ];
                }
            };

            $registry = new \ZenAiAssistContentRegistry($projectRoot, $bundledPluginRoot, $runtimeInspector);
            $guidance = new \ZenAiAssistGuidanceService($registry);
            $skills = new \ZenAiAssistSkillService($registry);

            $topics = $guidance->listTopics();
            $topicsBySlug = [];
            foreach ($topics as $topic) {
                $topicsBySlug[$topic['topic']] = $topic;
            }

            $this->assertSame('core', $topicsBySlug['core-pages']['source']['type']);
            $this->assertSame('plugin', $topicsBySlug['plugin-runtime']['source']['type']);
            $this->assertSame('bundled', $topicsBySlug['bundled-topic']['source']['type']);

            $sharedSkill = $skills->getSkill('shared-skill');
            $pluginOnly = $skills->getSkill('plugin-only');
            $bundledOnly = $skills->getSkill('bundled-only');

            $this->assertTrue($sharedSkill['found']);
            $this->assertSame('Core Shared Skill', $sharedSkill['title']);
            $this->assertSame('core', $sharedSkill['source']['type']);
            $this->assertTrue($pluginOnly['found']);
            $this->assertSame('plugin', $pluginOnly['source']['type']);
            $this->assertSame('debug-bar', $pluginOnly['source']['plugin']['unique_key']);
            $this->assertTrue($bundledOnly['found']);
            $this->assertSame('bundled', $bundledOnly['source']['type']);
        } finally {
            $this->removeDirectory(rtrim($projectRoot, '/\\'));
        }
    }

    private function makeTempDirectory(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(4));
        mkdir($path, 0775, true);

        return rtrim($path, '/\\') . '/';
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents);
    }

    private function writeExamplePlugin(string $pluginRoot): void
    {
        $this->writeFile($pluginRoot . 'manifest.php', "<?php\nreturn ['pluginVersion' => 'v1.0.0', 'pluginName' => 'Example', 'pluginDescription' => 'Example plugin', 'pluginAuthor' => 'Tester', 'pluginId' => 0, 'zcVersions' => []];\n");
        $this->writeFile($pluginRoot . 'filenames.php', "<?php\ndefine('FILENAME_EXAMPLE', 'example');\n");
        $this->writeFile($pluginRoot . 'Installer/ScriptedInstaller.php', "<?php\nclass ScriptedInstaller { public function validateInstall() {} public function executeInstall() { zen_register_admin_page('toolsExample', 'BOX_TOOLS_EXAMPLE', 'FILENAME_EXAMPLE', '', 'tools', 'Y', 20); } public function executeUninstall() {} }\n");
        $this->writeFile($pluginRoot . 'Installer/languages/english/main.php', "<?php\nreturn [];\n");
        $this->writeFile($pluginRoot . 'catalog/includes/modules/pages/example/header_php.php', "<?php\n");
        $this->writeFile($pluginRoot . 'catalog/includes/languages/english/lang.example.php', "<?php\nreturn ['TEXT_EXAMPLE' => 'Example'];\n");
        $this->writeFile($pluginRoot . 'catalog/includes/templates/template_default/tpl_example.php', "<?php\n");
        $this->writeFile($pluginRoot . 'admin/example.php', "<?php\n");
        $this->writeFile($pluginRoot . 'admin/includes/languages/english/lang.example.php', "<?php\nreturn ['HEADING_TITLE' => 'Example'];\n");
        $this->writeFile($pluginRoot . 'admin/includes/languages/english/extra_definitions/lang.example_menu.php', "<?php\nreturn ['BOX_TOOLS_EXAMPLE' => 'Example'];\n");
        $this->writeFile($pluginRoot . 'catalog/includes/classes/observers/auto_ExampleObserver.php', "<?php\nclass auto_ExampleObserver extends base { public function __construct() { \$this->attach(\$this, ['NOTIFY_HEADER_START_INDEX']); } public function update(&\$class, \$eventID, \$paramsArray = []) {} }\n");
        $this->writeFile($pluginRoot . 'resources/skills/plugin-workflow.md', "# Example Plugin Workflow\n\nChecklist\n");
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
                continue;
            }

            unlink($fileInfo->getPathname());
        }

        rmdir($directory);
    }
}
