<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField;

/**
 * Keyed strategy registry: exactly one renderer/formatter resolves per registry key.
 * Populated at bootstrap with core's own zen_cfg_* / zen_get_* adapters (see
 * CoreRegistryBootstrap); plugins may register additional keys of their own.
 * @since ZC v3.0.0
 */
class ConfigFieldRegistry
{
    /** @var array<string, ConfigFieldRendererInterface|class-string<ConfigFieldRendererInterface>> */
    protected array $renderers = [];

    /** @var array<string, ConfigFieldFormatterInterface|class-string<ConfigFieldFormatterInterface>> */
    protected array $formatters = [];

    public function registerRenderer(string $key, ConfigFieldRendererInterface $renderer): void
    {
        $this->renderers[$key] = $renderer;
    }

    public function registerFormatter(string $key, ConfigFieldFormatterInterface $formatter): void
    {
        $this->formatters[$key] = $formatter;
    }

    /**
     * Lazy variant of registerRenderer(): registers a class name instead of an instance, so
     * the class is only instantiated the first time this key is actually resolved (avoids
     * eagerly constructing every plugin-registered renderer on every admin page load, even
     * ones never rendered on that page). The interface is checked immediately via reflection
     * (no instantiation needed), so a misconfigured registration fails fast at bootstrap time.
     *
     * @param class-string<ConfigFieldRendererInterface> $className
     */
    public function registerRendererClass(string $key, string $className): void
    {
        if (!is_a($className, ConfigFieldRendererInterface::class, true)) {
            throw new \InvalidArgumentException("$className must implement ConfigFieldRendererInterface");
        }
        $this->renderers[$key] = $className;
    }

    /**
     * @param class-string<ConfigFieldFormatterInterface> $className
     */
    public function registerFormatterClass(string $key, string $className): void
    {
        if (!is_a($className, ConfigFieldFormatterInterface::class, true)) {
            throw new \InvalidArgumentException("$className must implement ConfigFieldFormatterInterface");
        }
        $this->formatters[$key] = $className;
    }

    public function hasRenderer(string $key): bool
    {
        return isset($this->renderers[$key]);
    }

    public function hasFormatter(string $key): bool
    {
        return isset($this->formatters[$key]);
    }

    public function resolveRenderer(string $key): ?ConfigFieldRendererInterface
    {
        $entry = $this->renderers[$key] ?? null;
        if (is_string($entry)) {
            $entry = new $entry();
            $this->renderers[$key] = $entry; // memoize so repeat resolutions don't re-instantiate
        }
        return $entry;
    }

    public function resolveFormatter(string $key): ?ConfigFieldFormatterInterface
    {
        $entry = $this->formatters[$key] ?? null;
        if (is_string($entry)) {
            $entry = new $entry();
            $this->formatters[$key] = $entry;
        }
        return $entry;
    }

    /**
     * Convenience used by call sites; returns null if nothing is registered under $key.
     */
    public function render(string $key, string $value, string $fieldName, array $params = []): ?string
    {
        return $this->resolveRenderer($key)?->render($value, $fieldName, $params);
    }

    public function format(string $key, string $value, array $params = []): ?string
    {
        return $this->resolveFormatter($key)?->format($value, $params);
    }

    /**
     * Registers core's own renderer/formatter adapters. Called once at bootstrap
     * (see admin/includes/auto_loaders/config.configField.php).
     */
    public function bootstrapCore(): void
    {
        CoreRegistryBootstrap::register($this);
    }
}
