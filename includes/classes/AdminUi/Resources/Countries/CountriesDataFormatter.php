<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Countries;

use Zencart\ViewBuilders\NativePaginator;
use Zencart\ViewBuilders\SimpleDataFormatter;
use Zencart\ViewBuilders\TableViewDefinition;

class CountriesDataFormatter extends SimpleDataFormatter
{
    public function __construct(
        \Zencart\Request\Request $request,
        TableViewDefinition $tableViewDefinition,
        NativePaginator $resultSet,
        $derivedItems,
        protected string $currentPage,
        protected bool $alphabeticMode = false
    ) {
        parent::__construct($request, $tableViewDefinition, $resultSet, $derivedItems);
    }

    protected function getCurrentPagerValue()
    {
        return $this->currentPage !== '' ? $this->currentPage : null;
    }

    public function searchAction(): string
    {
        return FILENAME_COUNTRIES;
    }

    public function isAlphabeticMode(): bool
    {
        return $this->alphabeticMode;
    }
}
