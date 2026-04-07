<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ViewBuilders;

class SortState
{
    public function __construct(
        protected string $column,
        protected string $direction,
        protected string $sortExpression
    ) {
        $this->direction = strtolower($this->direction) === 'desc' ? 'desc' : 'asc';
    }

    public function column(): string
    {
        return $this->column;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function sortExpression(): string
    {
        return $this->sortExpression;
    }

    public function nextDirection(): string
    {
        return $this->direction === 'asc' ? 'desc' : 'asc';
    }

    public function toOrderByClause(): string
    {
        return $this->sortExpression . ' ' . strtoupper($this->direction);
    }
}
