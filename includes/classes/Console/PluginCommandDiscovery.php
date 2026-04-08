<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Console;

use DirectoryIterator;
use Throwable;

class PluginCommandDiscovery
{
    /**
     * @var string[]
     */
    private array $errors = [];

    public function __construct(
        private string $pluginRootPath,
        private ?\Aura\Autoload\Loader $autoloader = null
    ) {
    }

    /**
     * @return ConsoleCommand[]
     */
    public function discover(): array
    {
        $commands = [];
        $this->errors = [];

        if (!is_dir($this->pluginRootPath)) {
            return [];
        }

        foreach (new DirectoryIterator($this->pluginRootPath) as $pluginDirectory) {
            if ($pluginDirectory->isDot() || !$pluginDirectory->isDir()) {
                continue;
            }

            foreach (new DirectoryIterator($pluginDirectory->getPathname()) as $versionDirectory) {
                if ($versionDirectory->isDot() || !$versionDirectory->isDir()) {
                    continue;
                }

                $versionPath = $versionDirectory->getPathname();
                if (!file_exists($versionPath . '/manifest.php')) {
                    continue;
                }

                $this->registerPluginConsoleNamespace($pluginDirectory->getFilename(), $versionPath);
                $commands = array_merge($commands, $this->loadCommandsFromVersion($versionPath));
            }
        }

        return $commands;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function registerPluginConsoleNamespace(string $pluginKey, string $versionPath): void
    {
        if ($this->autoloader === null) {
            return;
        }

        $consolePath = $versionPath . '/Console/';
        if (!is_dir($consolePath)) {
            return;
        }

        $namespace = 'Zencart\\Plugins\\Console\\' . $this->normalizePluginNamespace($pluginKey);
        $this->autoloader->addPrefix($namespace, $consolePath);
    }

    /**
     * @return ConsoleCommand[]
     */
    private function loadCommandsFromVersion(string $versionPath): array
    {
        $commandFile = $versionPath . '/Console/commands.php';
        if (!file_exists($commandFile)) {
            return [];
        }

        try {
            $definitions = require $commandFile;
        } catch (Throwable $exception) {
            $this->errors[] = sprintf(
                'Failed loading plugin commands from %s: %s',
                $commandFile,
                $exception->getMessage()
            );
            return [];
        }

        if (!is_array($definitions)) {
            $this->errors[] = 'Plugin command definition file must return an array: ' . $commandFile;
            return [];
        }

        $commands = [];
        foreach ($definitions as $definition) {
            try {
                $commands[] = $this->resolveCommandDefinition($definition);
            } catch (Throwable $exception) {
                $this->errors[] = sprintf(
                    'Invalid plugin command definition in %s: %s',
                    $commandFile,
                    $exception->getMessage()
                );
            }
        }

        return $commands;
    }

    private function resolveCommandDefinition(mixed $definition): ConsoleCommand
    {
        if ($definition instanceof ConsoleCommand) {
            return $definition;
        }

        if (is_string($definition) && class_exists($definition) && is_subclass_of($definition, ConsoleCommand::class)) {
            return new $definition();
        }

        throw new \InvalidArgumentException('Definitions must be ConsoleCommand instances or ConsoleCommand class names.');
    }

    private function normalizePluginNamespace(string $pluginKey): string
    {
        $segments = preg_split('/[^a-zA-Z0-9]+/', $pluginKey) ?: [];
        $segments = array_filter($segments, static fn ($segment) => $segment !== '');

        if ($segments === []) {
            return 'Plugin';
        }

        return implode('', array_map(static fn ($segment) => ucfirst((string) $segment), $segments));
    }
}
