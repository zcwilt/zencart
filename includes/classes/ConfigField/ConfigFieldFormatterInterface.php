<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField;

/**
 * Replaces the legacy configuration.use_function dispatch mechanism: produces
 * read-only display text/HTML for a configuration value.
 * @since ZC v3.0.0
 */
interface ConfigFieldFormatterInterface
{
    /**
     * @param string $value  Current stored configuration_value.
     * @param array  $params Formatter-specific parameters decoded from the row's
     *                       'renderer' JSON payload.
     * @return string        Display text/HTML.
     */
    public function format(string $value, array $params = []): string;
}
