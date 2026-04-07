<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\AdminUi\Pages\CrudListPageBuilder;
use Zencart\AdminUi\Pages\SplitPageResultsFactory;
use Zencart\Request\Request;
use Zencart\ViewBuilders\BaseController;
use Zencart\ViewBuilders\DataTableDataSource;

class CrudListPageBuilderTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/Request.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/AdminPageData.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ListViewConfig.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ListFooterConfig.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ResourceListPage.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/SplitPageResultsFactory.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/PaginatedResourceListPageBuilder.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/CrudListPageBuilder.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/TableViewDefinition.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/NativePaginator.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/DerivedItemsManager.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/SimpleDataFormatter.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/BaseController.php';
        require_once DIR_FS_CATALOG . 'includes/classes/ViewBuilders/DataTableDataSource.php';
    }

    public function testCrudListPageBuilderBuildsPaginatedResourcePage(): void
    {
        $_REQUEST = ['page' => 2, 'cmd' => 'tax_classes'];
        $request = Request::capture();
        $messageStack = new \stdClass();

        $factory = new class extends SplitPageResultsFactory {
            public function create(
                int &$currentPage,
                int $resultsPerPage,
                string &$query,
                int &$queryNumRows,
                string $letterGroupColumn = '',
                int $letterGroupLength = 0
            ): object {
                $queryNumRows = 4;

                return new class {
                    public function display_count($queryNumRows, $resultsPerPage, $currentPage, $countText): string
                    {
                        return sprintf('count:%d:%d:%d:%s', $queryNumRows, $resultsPerPage, $currentPage, $countText);
                    }

                    public function display_links($queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage, $linkParameters = '', $pageVariable = 'page'): string
                    {
                        return sprintf('links:%d:%d:%d:%d:%s:%s', $queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage, $linkParameters, $pageVariable);
                    }
                };
            }
        };

        $page = (new CrudListPageBuilder($request, $messageStack, 'Tax Classes'))
            ->withTableDefinition([
                'colKey' => 'tax_class_id',
                'colKeyName' => 'tID',
                'maxRowCount' => 20,
                'selectedRowAction' => 'edit',
                'columns' => [
                    'tax_class_id' => ['title' => 'ID'],
                    'tax_class_title' => ['title' => 'Tax Class'],
                ],
            ])
            ->withDataSourceClass(FakeTaxClassDataSource::class)
            ->withControllerClass(FakeTaxClassController::class)
            ->withPagination('SELECT * FROM tax_class', 'display text', 20, 5)
            ->withPrimaryAction('tax_classes.php?page=2&action=new', 'New Tax Class')
            ->withSplitPageResultsFactory($factory)
            ->build();

        $viewData = $page->viewData();
        $this->assertSame(DIR_FS_ADMIN . 'includes/templates/resource_list.php', $page->templatePath());
        $this->assertSame('Tax Classes', $viewData['pageHeading']);
        $this->assertSame('count:4:20:2:display text', $viewData['footerConfig']->countHtml());
        $this->assertSame('links:4:20:5:2::page', $viewData['footerConfig']->linksHtml());
        $this->assertSame('tax_classes.php?page=2&action=new', $viewData['footerConfig']->primaryActionHref());
        $this->assertSame('New Tax Class', $viewData['footerConfig']->primaryActionLabel());
        $this->assertSame('<h4>Fake Tax Class</h4>', $viewData['tableController']->getBoxHeader()[0]['text']);
    }
}

class FakeTaxClassDataSource extends DataTableDataSource
{
    protected function buildInitialQuery(\Zencart\Request\Request $request): array
    {
        return [
            ['tax_class_id' => 1, 'tax_class_title' => 'Fake Tax Class'],
            ['tax_class_id' => 2, 'tax_class_title' => 'Shipping'],
        ];
    }
}

class FakeTaxClassController extends BaseController
{
    protected function processDefaultAction(): void
    {
        $this->setBoxHeader('<h4>Fake Tax Class</h4>');
    }
}
