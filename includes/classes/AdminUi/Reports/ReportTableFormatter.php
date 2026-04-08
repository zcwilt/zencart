<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

class ReportTableFormatter
{
    protected $rowLinkResolver;

    public function __construct(
        protected array $headers,
        protected array $rows,
        ?callable $rowLinkResolver = null,
        protected array $toolbarConfig = []
    ) {
        $this->rowLinkResolver = $rowLinkResolver;
    }

    public function getTableHeaders(): array
    {
        return $this->headers;
    }

    public function getTableData(): array
    {
        return $this->rows;
    }

    public function rowLink(array $tableRow): ?string
    {
        if ($this->rowLinkResolver === null) {
            return null;
        }

        $link = ($this->rowLinkResolver)($tableRow);
        return $link === '' ? null : $link;
    }

    public function hasSearch(): bool
    {
        return !empty($this->toolbarConfig['hasSearch']);
    }

    public function hasFilters(): bool
    {
        return !empty($this->toolbarConfig['filters']);
    }

    public function filters(): array
    {
        return $this->toolbarConfig['filters'] ?? [];
    }

    public function searchValue(): string
    {
        return (string) ($this->toolbarConfig['searchValue'] ?? '');
    }

    public function searchParameter(): string
    {
        return (string) ($this->toolbarConfig['searchParameter'] ?? 'search');
    }

    public function searchPlaceholder(): string
    {
        return (string) ($this->toolbarConfig['searchPlaceholder'] ?? '');
    }

    public function searchAction(): string
    {
        return (string) ($this->toolbarConfig['searchAction'] ?? '');
    }

    public function searchHiddenParameters(): array
    {
        return $this->toolbarConfig['hiddenParameters'] ?? [];
    }

    public function toolbarHiddenParameters(): array
    {
        return $this->searchHiddenParameters();
    }

    public function searchResetHref(): string
    {
        return (string) ($this->toolbarConfig['resetHref'] ?? '');
    }
}
