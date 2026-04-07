<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

use InvalidArgumentException;
use Zencart\AdminUi\AdminPageData;
use Zencart\Request\Request;
use Zencart\Traits\NotifierManager;
use Zencart\ViewBuilders\DataTableDataSource;
use Zencart\ViewBuilders\DerivedItemsManager;
use Zencart\ViewBuilders\SimpleDataFormatter;
use Zencart\ViewBuilders\TableViewDefinition;

class CrudListPageBuilder
{
    use NotifierManager;

    protected ?array $tableDefinition = null;
    protected ?string $dataSourceClass = null;
    protected ?string $controllerClass = null;
    protected ?ListViewConfig $listViewConfig = null;
    protected ?array $paginationConfig = null;
    protected ?string $primaryActionHref = null;
    protected ?string $primaryActionLabel = null;
    protected ?SplitPageResultsFactory $splitPageResultsFactory = null;
    protected $controllerInitializer = null;

    public function __construct(
        protected Request $request,
        protected $messageStack,
        protected string $heading
    ) {
    }

    public function withTableDefinition(array $tableDefinition): self
    {
        $this->tableDefinition = $tableDefinition;
        return $this;
    }

    public function withDataSourceClass(string $dataSourceClass): self
    {
        $this->dataSourceClass = $dataSourceClass;
        return $this;
    }

    public function withControllerClass(string $controllerClass): self
    {
        $this->controllerClass = $controllerClass;
        return $this;
    }

    public function withControllerInitializer(callable $initializer): self
    {
        $this->controllerInitializer = $initializer;
        return $this;
    }

    public function withListViewConfig(?ListViewConfig $listViewConfig): self
    {
        $this->listViewConfig = $listViewConfig;
        return $this;
    }

    public function withPagination(
        string $query,
        string $countText,
        int $resultsPerPage,
        int $maxPageLinks = MAX_DISPLAY_PAGE_LINKS,
        string $pageVariable = 'page',
        string $linkParameters = ''
    ): self {
        $this->paginationConfig = [
            'query' => $query,
            'countText' => $countText,
            'resultsPerPage' => $resultsPerPage,
            'maxPageLinks' => $maxPageLinks,
            'pageVariable' => $pageVariable,
            'linkParameters' => $linkParameters,
        ];
        return $this;
    }

    public function withPrimaryAction(?string $href, ?string $label): self
    {
        $this->primaryActionHref = $href;
        $this->primaryActionLabel = $label;
        return $this;
    }

    public function withSplitPageResultsFactory(SplitPageResultsFactory $splitPageResultsFactory): self
    {
        $this->splitPageResultsFactory = $splitPageResultsFactory;
        return $this;
    }

    public function build(): AdminPageData
    {
        $heading = $this->heading;
        $tableDefinition = $this->requireValue($this->tableDefinition, 'table definition');
        $dataSourceClass = $this->requireValue($this->dataSourceClass, 'data source class');
        $controllerClass = $this->requireValue($this->controllerClass, 'controller class');
        $listViewConfig = $this->listViewConfig;
        $paginationConfig = $this->paginationConfig;
        $primaryActionHref = $this->primaryActionHref;
        $primaryActionLabel = $this->primaryActionLabel;
        $controllerInitializer = $this->controllerInitializer;

        $this->notify(
            'NOTIFY_ADMIN_CRUD_LIST_BUILD_START',
            [],
            $heading,
            $tableDefinition,
            $dataSourceClass,
            $controllerClass,
            $listViewConfig,
            $paginationConfig,
            $primaryActionHref,
            $primaryActionLabel
        );

        $table = new TableViewDefinition($tableDefinition);

        /** @var DataTableDataSource $dataSource */
        $dataSource = new $dataSourceClass($table);
        $query = $dataSource->processRequest($this->request);
        $queryResults = $dataSource->processQuery($query);
        $formatter = new SimpleDataFormatter($this->request, $table, $queryResults, new DerivedItemsManager());

        $controller = new $controllerClass($this->request, $this->messageStack, $table, $formatter);
        if ($controllerInitializer !== null) {
            $controllerInitializer($controller);
        }
        $controller->processRequest();

        $pageBuilder = new PaginatedResourceListPageBuilder(
            $this->request,
            $heading,
            $formatter,
            $controller,
            $listViewConfig,
            $this->splitPageResultsFactory
        );

        if ($paginationConfig !== null) {
            $pageBuilder->withPagination(
                $paginationConfig['query'],
                $paginationConfig['countText'],
                $paginationConfig['resultsPerPage'],
                $paginationConfig['maxPageLinks'],
                $paginationConfig['pageVariable'],
                $paginationConfig['linkParameters']
            );
        }

        $page = $pageBuilder
            ->withPrimaryAction($primaryActionHref, $primaryActionLabel)
            ->build();

        $this->notify('NOTIFY_ADMIN_CRUD_LIST_BUILD_END', [], $page);

        return $page;
    }

    protected function requireValue($value, string $label)
    {
        if ($value === null) {
            throw new InvalidArgumentException('Missing required ' . $label . ' for CRUD list page builder.');
        }

        return $value;
    }
}
