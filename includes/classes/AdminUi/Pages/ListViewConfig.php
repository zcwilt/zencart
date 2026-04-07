<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

class ListViewConfig
{
    public function __construct(
        protected ?string $groupByField = null,
        protected array $groupOrder = [],
        protected array $groupLabels = [],
        protected array $columnWidths = []
    ) {
    }

    public function groupByField(): ?string
    {
        return $this->groupByField;
    }

    public function groupOrder(): array
    {
        return $this->groupOrder;
    }

    public function groupLabels(): array
    {
        return $this->groupLabels;
    }

    public function columnWidths(): array
    {
        return $this->columnWidths;
    }
}
