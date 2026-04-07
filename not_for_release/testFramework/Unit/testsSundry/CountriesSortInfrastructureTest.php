<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace {
    if (!function_exists('zen_href_link')) {
        function zen_href_link($page, $parameters = '')
        {
            return $page . ($parameters !== '' ? '?' . $parameters : '');
        }
    }
}

namespace Tests\Unit\testsSundry {

use Tests\Support\zcUnitTestCase;
use Zencart\AdminUi\Resources\Countries\CountriesDataFormatter;
use Zencart\AdminUi\Resources\Countries\CountriesDataSource;
use Zencart\Request\Request;
use Zencart\ViewBuilders\DerivedItemsManager;
use Zencart\ViewBuilders\NativePaginator;
use Zencart\ViewBuilders\TableViewDefinition;

class CountriesSortInfrastructureTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!defined('MAX_DISPLAY_SEARCH_RESULTS')) {
            define('MAX_DISPLAY_SEARCH_RESULTS', 20);
        }
        require_once DIR_FS_CATALOG . 'includes/classes/Request.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/TableViewDefinition.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/NativePaginator.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/DerivedItemsManager.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/SimpleDataFormatter.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/DataTableDataSource.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Resources/Countries/CountriesDataSource.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Resources/Countries/CountriesDataFormatter.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/SortState.php';
    }

    public function testCountriesFormatterBuildsSortableHeaderAndPreservesSortStateInRowLinks(): void
    {
        $_REQUEST = [
            'cmd' => 'countries',
            'page' => '2',
            'sort' => 'countries_name',
            'direction' => 'asc',
            'cID' => '5',
        ];
        $request = Request::capture();

        $table = new TableViewDefinition([
            'colKey' => 'countries_id',
            'colKeyName' => 'cID',
            'sortParameter' => 'sort',
            'sortDirectionParameter' => 'direction',
            'defaultSort' => ['column' => 'countries_name', 'direction' => 'asc'],
            'columns' => [
                'countries_id' => ['title' => 'ID'],
                'countries_name' => ['title' => 'Country', 'sortable' => true, 'sortKey' => 'countries_name'],
                'status' => ['title' => 'Status'],
            ],
        ]);

        $formatter = new CountriesDataFormatter(
            $request,
            $table,
            new NativePaginator([
                ['countries_id' => '5', 'countries_name' => 'Belgium', 'status' => '1'],
            ], 1, 20, 1),
            new DerivedItemsManager(),
            '2'
        );

        $headers = $formatter->getTableHeaders();
        $row = $formatter->getTableData()[0];

        $this->assertNull($headers[0]['href']);
        $this->assertSame('countries_name', $headers[1]['sortField']);
        $this->assertSame('asc', $headers[1]['sortDirection']);
        $this->assertSame('desc', $headers[1]['nextSortDirection']);
        $this->assertStringContainsString('sort=countries_name', $headers[1]['href']);
        $this->assertStringContainsString('direction=desc', $headers[1]['href']);
        $this->assertStringNotContainsString('page=2', $headers[1]['href']);
        $this->assertStringContainsString('page=2', $formatter->getSelectedRowLink($row));
        $this->assertStringContainsString('sort=countries_name', $formatter->getSelectedRowLink($row));
        $this->assertStringContainsString('direction=asc', $formatter->getSelectedRowLink($row));
        $this->assertSame('page=2&sort=countries_name&direction=asc', $formatter->getPersistentLinkParameters(['cID']));
    }

    public function testCountriesDataSourceDoesNotRepaginateSqlSlicedResults(): void
    {
        $table = new TableViewDefinition([
            'colKey' => 'countries_id',
            'colKeyName' => 'cID',
            'columns' => [
                'countries_id' => ['title' => 'ID'],
                'countries_name' => ['title' => 'Country'],
            ],
        ]);

        $dataSource = new class($table) extends CountriesDataSource {
            public function __construct(TableViewDefinition $tableViewDefinition)
            {
                parent::__construct($tableViewDefinition);
                $this->currentPage = '2';
                $this->queryNumRows = 40;
            }

            protected function buildInitialQuery(Request $request): array
            {
                return [];
            }
        };

        $results = $dataSource->processQuery([
            ['countries_id' => '21', 'countries_name' => 'Country 21'],
            ['countries_id' => '22', 'countries_name' => 'Country 22'],
        ]);

        $this->assertSame(2, $results->currentPage());
        $this->assertSame(40, $results->total());
        $this->assertSame(20, $results->perPage());
        $this->assertCount(2, $results->getCollection());
    }
}
}
