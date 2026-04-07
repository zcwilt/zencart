<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\AdminUi\Pages\PaginatedResourceListPageBuilder;
use Zencart\AdminUi\Pages\SplitPageResultsFactory;
use Zencart\Request\Request;

class PaginatedResourceListPageBuilderTest extends zcUnitTestCase
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
    }

    public function testBuilderCreatesResourceListPageWithPaginatedFooterConfig(): void
    {
        $_REQUEST = ['page' => 2, 'action' => '', 'cmd' => 'tax_classes'];
        $request = Request::capture();
        $formatter = new \stdClass();
        $tableController = new \stdClass();

        $factory = new class extends SplitPageResultsFactory {
            public array $captured = [];

            public function create(
                int &$currentPage,
                int $resultsPerPage,
                string &$query,
                int &$queryNumRows,
                string $letterGroupColumn = '',
                int $letterGroupLength = 0
            ): object {
                $this->captured = [
                    'currentPage' => $currentPage,
                    'resultsPerPage' => $resultsPerPage,
                    'query' => $query,
                ];
                $queryNumRows = 12;

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

        $page = (new PaginatedResourceListPageBuilder(
            $request,
            'Tax Classes',
            $formatter,
            $tableController,
            null,
            $factory
        ))
            ->withPagination(
                'SELECT tax_class_id FROM tax_class ORDER BY tax_class_title',
                'display text',
                20,
                5,
                'page',
                'foo=bar'
            )
            ->withPrimaryAction('tax_classes.php?page=2&action=new', 'New Tax Class')
            ->build();

        $this->assertSame(DIR_FS_ADMIN . 'includes/templates/resource_list.php', $page->templatePath());
        $viewData = $page->viewData();
        $this->assertSame('Tax Classes', $viewData['pageHeading']);
        $this->assertSame($formatter, $viewData['formatter']);
        $this->assertSame($tableController, $viewData['tableController']);
        $this->assertSame(2, $factory->captured['currentPage']);
        $this->assertSame(20, $factory->captured['resultsPerPage']);
        $this->assertStringContainsString('SELECT tax_class_id', $factory->captured['query']);
        $this->assertSame('count:12:20:2:display text', $viewData['footerConfig']->countHtml());
        $this->assertSame('links:12:20:5:2:foo=bar:page', $viewData['footerConfig']->linksHtml());
        $this->assertSame('tax_classes.php?page=2&action=new', $viewData['footerConfig']->primaryActionHref());
        $this->assertSame('New Tax Class', $viewData['footerConfig']->primaryActionLabel());
    }

    public function testBuilderNormalizesNullPaginationLinksToEmptyString(): void
    {
        $_REQUEST = ['page' => 1, 'action' => '', 'cmd' => 'tax_classes'];
        $request = Request::capture();
        $formatter = new \stdClass();
        $tableController = new \stdClass();

        $factory = new class extends SplitPageResultsFactory {
            public function create(
                int &$currentPage,
                int $resultsPerPage,
                string &$query,
                int &$queryNumRows,
                string $letterGroupColumn = '',
                int $letterGroupLength = 0
            ): object {
                $queryNumRows = 1;

                return new class {
                    public function display_count($queryNumRows, $resultsPerPage, $currentPage, $countText): string
                    {
                        return 'count';
                    }

                    public function display_links($queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage, $linkParameters = '', $pageVariable = 'page')
                    {
                        return null;
                    }
                };
            }
        };

        $page = (new PaginatedResourceListPageBuilder(
            $request,
            'Tax Classes',
            $formatter,
            $tableController,
            null,
            $factory
        ))
            ->withPagination(
                'SELECT tax_class_id FROM tax_class ORDER BY tax_class_title',
                'display text',
                20,
                5
            )
            ->build();

        $viewData = $page->viewData();
        $this->assertSame('count', $viewData['footerConfig']->countHtml());
        $this->assertSame('', $viewData['footerConfig']->linksHtml());
    }
}
