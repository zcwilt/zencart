<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

use Zencart\AdminUi\AdminPageData;
use Zencart\Request\Request;
use Zencart\Traits\NotifierManager;

class PaginatedResourceListPageBuilder
{
    use NotifierManager;

    protected ?array $paginationConfig = null;
    protected ?string $primaryActionHref = null;
    protected ?string $primaryActionLabel = null;

    public function __construct(
        protected Request $request,
        protected string $heading,
        protected $formatter,
        protected $tableController,
        protected ?ListViewConfig $listViewConfig = null,
        protected ?SplitPageResultsFactory $splitPageResultsFactory = null
    ) {
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

    public function build(): AdminPageData
    {
        $heading = $this->heading;
        $formatter = $this->formatter;
        $tableController = $this->tableController;
        $listViewConfig = $this->listViewConfig;
        $paginationConfig = $this->paginationConfig;
        $primaryActionHref = $this->primaryActionHref;
        $primaryActionLabel = $this->primaryActionLabel;

        $this->notify(
            'NOTIFY_ADMIN_PAGINATED_LIST_BUILD_START',
            [],
            $heading,
            $formatter,
            $tableController,
            $listViewConfig,
            $paginationConfig,
            $primaryActionHref,
            $primaryActionLabel
        );

        $this->heading = $heading;
        $this->formatter = $formatter;
        $this->tableController = $tableController;
        $this->listViewConfig = $listViewConfig;
        $this->paginationConfig = $paginationConfig;
        $this->primaryActionHref = $primaryActionHref;
        $this->primaryActionLabel = $primaryActionLabel;

        $page = (new ResourceListPage(
            $heading,
            $formatter,
            $tableController,
            $listViewConfig,
            $this->buildFooterConfig()
        ))->build();

        $this->notify('NOTIFY_ADMIN_PAGINATED_LIST_BUILD_END', [], $page);

        return $page;
    }

    protected function buildFooterConfig(): ListFooterConfig
    {
        if ($this->paginationConfig === null) {
            return new ListFooterConfig(
                '',
                '',
                $this->primaryActionHref,
                $this->primaryActionLabel
            );
        }

        $currentPage = max(1, $this->request->integer($this->paginationConfig['pageVariable'], 1));
        $query = $this->paginationConfig['query'];
        $queryNumRows = 0;

        $split = ($this->splitPageResultsFactory ?? new SplitPageResultsFactory())->create(
            $currentPage,
            $this->paginationConfig['resultsPerPage'],
            $query,
            $queryNumRows
        );

        return new ListFooterConfig(
            (string) $split->display_count(
                $queryNumRows,
                $this->paginationConfig['resultsPerPage'],
                $currentPage,
                $this->paginationConfig['countText']
            ),
            (string) ($split->display_links(
                $queryNumRows,
                $this->paginationConfig['resultsPerPage'],
                $this->paginationConfig['maxPageLinks'],
                $currentPage,
                $this->paginationConfig['linkParameters'],
                $this->paginationConfig['pageVariable']
            ) ?? ''),
            $this->primaryActionHref,
            $this->primaryActionLabel
        );
    }
}
