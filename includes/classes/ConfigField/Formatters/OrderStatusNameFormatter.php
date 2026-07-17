<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField\Formatters;

use Zencart\ConfigField\ConfigFieldFormatterInterface;

/** @see zen_get_order_status_name() */
class OrderStatusNameFormatter implements ConfigFieldFormatterInterface
{
    public function format(string $value, array $params = []): string
    {
        return zen_get_order_status_name($value);
    }
}
