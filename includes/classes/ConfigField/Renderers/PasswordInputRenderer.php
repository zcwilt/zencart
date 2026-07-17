<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField\Renderers;

use Zencart\ConfigField\ConfigFieldRendererInterface;
use Zencart\ConfigField\SensitiveConfigFieldInterface;

/** @see zen_cfg_password_input() */
class PasswordInputRenderer implements ConfigFieldRendererInterface, SensitiveConfigFieldInterface
{
    public function render(string $value, string $fieldName, array $params = []): string
    {
        return zen_cfg_password_input($value, $fieldName);
    }

    public function isSensitive(): bool
    {
        return true;
    }
}
