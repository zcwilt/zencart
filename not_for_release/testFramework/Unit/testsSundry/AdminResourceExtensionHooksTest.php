<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\CrudListPageBuilder;
use Zencart\AdminUi\Pages\SplitPageResultsFactory;
use Zencart\AdminUi\Resources\AdminResource;
use Zencart\Request\Request;
use Zencart\Traits\ObserverManager;
use Zencart\ViewBuilders\BaseController;
use Zencart\ViewBuilders\DataTableDataSource;

class AdminResourceExtensionHooksTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!defined('MAX_DISPLAY_PAGE_LINKS')) {
            define('MAX_DISPLAY_PAGE_LINKS', 5);
        }
        require_once DIR_FS_CATALOG . 'includes/classes/EventDto.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/Singleton.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/NotifierManager.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/ObserverManager.php';
        require_once DIR_FS_CATALOG . 'includes/classes/Request.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/AdminPageData.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Resources/AdminResource.php';
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

    public function testCrudBuilderAndResourceHooksCanMutatePageOutput(): void
    {
        $_REQUEST = ['page' => 1, 'cmd' => 'tax_classes'];
        $request = Request::capture();
        $messageStack = new \stdClass();

        $observer = new class {
            use ObserverManager;

            public function __construct()
            {
                $this->attach($this, [
                    'NOTIFY_ADMIN_CRUD_LIST_BUILD_START',
                    'NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_END',
                ]);
            }

            public function updateNotifyAdminCrudListBuildStart(&$class, $eventId, $params, &$heading, &$tableDefinition, &$dataSourceClass, &$controllerClass, &$listViewConfig, &$paginationConfig, &$primaryActionHref, &$primaryActionLabel)
            {
                $primaryActionLabel = 'Observer Label';
            }

            public function updateNotifyAdminResourceBuildPageEnd(&$class, $eventId, $params, &$page)
            {
                $viewData = $page->viewData();
                $viewData['pageHeading'] = 'Observer Heading';
                $page = new AdminPageData($page->templatePath(), $viewData);
            }
        };

        $resource = new class($request, $messageStack, new class extends SplitPageResultsFactory {
            public function create(
                int &$currentPage,
                int $resultsPerPage,
                string &$query,
                int &$queryNumRows,
                string $letterGroupColumn = '',
                int $letterGroupLength = 0
            ): object {
                $queryNumRows = 2;

                return new class {
                    public function display_count($queryNumRows, $resultsPerPage, $currentPage, $countText): string
                    {
                        return 'count';
                    }

                    public function display_links($queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage, $linkParameters = '', $pageVariable = 'page'): string
                    {
                        return 'links';
                    }
                };
            }
        }) extends AdminResource {
            public function __construct($request, $messageStack, private $factory)
            {
                parent::__construct($request, $messageStack);
            }

            public function buildPage(): AdminPageData
            {
                $context = $this->notifyBuildPageStart();
                $page = (new CrudListPageBuilder($this->request, $this->messageStack, 'Original Heading'))
                    ->withTableDefinition([
                        'colKey' => 'tax_class_id',
                        'colKeyName' => 'tID',
                        'maxRowCount' => 20,
                        'columns' => [
                            'tax_class_id' => ['title' => 'ID'],
                        ],
                    ])
                    ->withDataSourceClass(HookFakeDataSource::class)
                    ->withControllerClass(HookFakeController::class)
                    ->withPagination('SELECT * FROM tax_class', 'display text', 20)
                    ->withPrimaryAction('tax_classes.php?action=new', 'Original Label')
                    ->withSplitPageResultsFactory($this->factory)
                    ->build();

                return $this->notifyBuildPageEnd($page, $context);
            }
        };

        $page = $resource->buildPage();
        $viewData = $page->viewData();

        $this->assertSame('Observer Heading', $viewData['pageHeading']);
        $this->assertSame('Observer Label', $viewData['footerConfig']->primaryActionLabel());
    }
}

class HookFakeDataSource extends DataTableDataSource
{
    protected function buildInitialQuery(\Zencart\Request\Request $request): array
    {
        return [
            ['tax_class_id' => 1],
        ];
    }
}

class HookFakeController extends BaseController
{
}
