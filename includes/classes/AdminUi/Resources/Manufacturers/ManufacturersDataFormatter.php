<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Manufacturers;

use Zencart\ViewBuilders\NativePaginator;
use Zencart\ViewBuilders\SimpleDataFormatter;
use Zencart\ViewBuilders\TableViewDefinition;

class ManufacturersDataFormatter extends SimpleDataFormatter
{
    public function __construct(
        \Zencart\Request\Request $request,
        TableViewDefinition $tableViewDefinition,
        NativePaginator $resultSet,
        $derivedItems,
        protected ?string $linkPage
    ) {
        parent::__construct($request, $tableViewDefinition, $resultSet, $derivedItems);
    }

    protected function getCurrentPagerValue()
    {
        return $this->linkPage !== null && $this->linkPage !== '' ? $this->linkPage : null;
    }

    public function editRowLink(array $tableRow): string
    {
        return $this->getSelectedRowLink($tableRow);
    }

    public function searchAction(): string
    {
        return FILENAME_MANUFACTURERS;
    }
}
