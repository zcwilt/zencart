<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField\Formatters;

use Zencart\ConfigField\ConfigFieldFormatterInterface;
use currencies;

/**
 * Adapter for the legacy `currencies->format` use_function convention
 * (Class->method dispatch, e.g. seen on MODULE_ORDER_TOTAL_LOWORDERFEE_ORDER_UNDER).
 * @see currencies::format()
 */
class CurrenciesFormatFormatter implements ConfigFieldFormatterInterface
{
    public function format(string $value, array $params = []): string
    {
        global $currencies;
        if (!is_object($currencies)) {
            $currencies = new currencies();
        }

        return $currencies->format($value);
    }
}
