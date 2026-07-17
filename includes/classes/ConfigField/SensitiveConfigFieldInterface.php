<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField;

/**
 * Optional marker interface — a renderer or formatter that knows its field should
 * never be echoed back in plaintext (passwords, API secrets). Purely advisory;
 * consulted alongside zcObserverLogEventListener::isSensitiveFieldName() by callers
 * that need to decide whether to redact a value.
 * @since ZC v3.0.0
 */
interface SensitiveConfigFieldInterface
{
    public function isSensitive(): bool;
}
