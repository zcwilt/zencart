<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

use Zencart\AdminUi\AdminPageData;
use Zencart\Request\Request;
use Zencart\Traits\NotifierManager;

class ReportListPageBuilder
{
    use NotifierManager;

    protected ?array $paginationConfig = null;
    protected int $totalOverride = 0;
    protected ?ReportViewConfig $viewConfig = null;
    protected ?SplitPageResultsFactory $splitPageResultsFactory = null;

    public function __construct(
        protected Request $request,
        protected string $heading,
        protected $formatter
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

    public function withTotalOverride(int $totalOverride): self
    {
        $this->totalOverride = max(0, $totalOverride);
        return $this;
    }

    public function withViewConfig(?ReportViewConfig $viewConfig): self
    {
        $this->viewConfig = $viewConfig;
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
        $formatter = $this->formatter;
        $viewConfig = $this->viewConfig;
        $paginationConfig = $this->paginationConfig;

        $this->notify(
            'NOTIFY_ADMIN_REPORT_LIST_BUILD_START',
            [],
            $heading,
            $formatter,
            $paginationConfig,
            $viewConfig
        );

        $page = (new ReportListPage(
            $heading,
            $formatter,
            $this->buildFooterConfig(),
            $viewConfig
        ))->build();

        $this->notify('NOTIFY_ADMIN_REPORT_LIST_BUILD_END', [], $page);

        return $page;
    }

    protected function buildFooterConfig(): ListFooterConfig
    {
        if ($this->paginationConfig === null) {
            return new ListFooterConfig();
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

        if ($this->totalOverride > 0) {
            $queryNumRows = $this->totalOverride;
        }

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
            ) ?? '')
        );
    }
}
