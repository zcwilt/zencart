<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\Request\Request;
use Zencart\ViewBuilders\DataTableDataSource;
use Zencart\ViewBuilders\TableViewDefinition;

class SearchableDataSourceTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/Request.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/TableViewDefinition.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/DataTableDataSource.php';
    }

    public function testSearchableDataSourceFiltersArrayResultsAcrossSearchableColumns(): void
    {
        $_REQUEST = [
            'cmd' => 'plugin_manager',
            'search' => 'ship',
        ];
        $request = Request::capture();

        $table = new TableViewDefinition([
            'columns' => [
                'name' => ['title' => 'Name', 'searchable' => true],
                'unique_key' => ['title' => 'Key', 'searchable' => true],
                'status' => ['title' => 'Status'],
            ],
        ]);

        $dataSource = new class($table) extends DataTableDataSource {
            protected function buildInitialQuery(Request $request): array
            {
                return [
                    ['name' => 'Taxable Goods', 'unique_key' => 'zc_tax', 'status' => 1],
                    ['name' => 'Shipping Manager', 'unique_key' => 'ship_manager', 'status' => 1],
                    ['name' => 'PayPal', 'unique_key' => 'payment_paypal', 'status' => 1],
                ];
            }
        };

        $rows = $dataSource->processRequest($request);

        $this->assertCount(1, $rows);
        $this->assertSame('Shipping Manager', $rows[0]['name']);
        $this->assertSame('ship_manager', $rows[0]['unique_key']);
    }
}
