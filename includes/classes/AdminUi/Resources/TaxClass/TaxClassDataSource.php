<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\TaxClass;

use Zencart\Request\Request;
use Zencart\ViewBuilders\DataTableDataSource;

class TaxClassDataSource extends DataTableDataSource
{
    protected function buildInitialQuery(Request $request): array
    {
        global $db;

        $rows = [];
        $query = $db->Execute(
            "SELECT tax_class_id, tax_class_title, tax_class_description, last_modified, date_added
               FROM " . TABLE_TAX_CLASS . "
              ORDER BY tax_class_title"
        );

        foreach ($query as $row) {
            $rows[] = $row;
        }

        return $rows;
    }
}
