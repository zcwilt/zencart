<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField;

/**
 * Replaces the legacy configuration.set_function eval() mechanism: produces the
 * HTML edit widget for a configuration value.
 * @since ZC v3.0.0
 */
interface ConfigFieldRendererInterface
{
    /**
     * @param string $value     Current stored (or posted-back) configuration_value.
     * @param string $fieldName The form field name/key to bind to, e.g. 'cfg_123'.
     * @param array  $params    Renderer-specific parameters decoded from the row's
     *                          'renderer' JSON payload (e.g. ['options' => ['true','false']]).
     * @return string           Raw HTML for the edit widget.
     */
    public function render(string $value, string $fieldName, array $params = []): string;
}
